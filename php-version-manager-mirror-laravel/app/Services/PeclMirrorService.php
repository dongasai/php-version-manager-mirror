<?php

namespace App\Services;

use App\Models\Mirror;
use App\Models\SyncJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * PECL镜像服务
 * 
 * 负责PECL扩展包的同步和管理
 */
class PeclMirrorService
{
    /**
     * 配置服务
     *
     * @var ConfigService
     */
    protected $configService;

    /**
     * 缓存服务
     *
     * @var CacheService
     */
    protected $cacheService;

    /**
     * 构造函数
     *
     * @param ConfigService $configService
     * @param CacheService $cacheService
     */
    public function __construct(ConfigService $configService, CacheService $cacheService)
    {
        $this->configService = $configService;
        $this->cacheService = $cacheService;
    }

    /**
     * 同步PECL镜像
     *
     * @param Mirror $mirror 镜像对象
     * @param SyncJob $syncJob 同步任务
     * @return bool
     */
    public function sync(Mirror $mirror, SyncJob $syncJob): bool
    {
        $this->updateJobLog($syncJob, "开始同步PECL扩展包...");

        $config = $mirror->config;
        $extensions = $config['extensions'] ?? [];

        if (empty($extensions)) {
            $this->updateJobLog($syncJob, "错误: PECL扩展配置为空");
            return false;
        }

        $dataDir = $this->configService->getDataDir() . '/pecl';
        $this->ensureDirectoryExists($dataDir);

        $successCount = 0;
        $totalExtensions = count($extensions);

        foreach ($extensions as $index => $extension) {
            $this->updateJobLog($syncJob, "同步PECL扩展: {$extension['name']} ({$index + 1}/{$totalExtensions})");

            if ($this->syncExtension($syncJob, $extension, $dataDir)) {
                $successCount++;
            }

            // 更新进度
            $progress = (int)(($index + 1) / $totalExtensions * 100);
            $syncJob->updateProgress($progress);
        }

        $this->updateJobLog($syncJob, "PECL同步完成，成功同步 {$successCount}/{$totalExtensions} 个扩展");
        return $successCount === $totalExtensions;
    }

    /**
     * 同步指定扩展
     *
     * @param Mirror $mirror 镜像对象
     * @param SyncJob $syncJob 同步任务
     * @param string $extensionName 扩展名
     * @return bool
     */
    public function syncExtension(Mirror $mirror, SyncJob $syncJob, string $extensionName): bool
    {
        $this->updateJobLog($syncJob, "同步PECL指定扩展: {$extensionName}");

        $config = $mirror->config;
        $extensions = $config['extensions'] ?? [];

        // 查找指定扩展
        $targetExtension = null;
        foreach ($extensions as $extension) {
            if ($extension['name'] === $extensionName) {
                $targetExtension = $extension;
                break;
            }
        }

        if (!$targetExtension) {
            $this->updateJobLog($syncJob, "错误: 扩展 {$extensionName} 不在配置列表中");
            return false;
        }

        $dataDir = $this->configService->getDataDir() . '/pecl';
        $this->ensureDirectoryExists($dataDir);

        return $this->syncExtension($syncJob, $targetExtension, $dataDir);
    }

    /**
     * 同步单个扩展
     *
     * @param SyncJob $syncJob 同步任务
     * @param array $extension 扩展配置
     * @param string $dataDir 数据目录
     * @return bool
     */
    protected function syncExtension(SyncJob $syncJob, array $extension, string $dataDir): bool
    {
        $name = $extension['name'];
        $versions = $extension['versions'] ?? [];

        if (empty($versions)) {
            $this->updateJobLog($syncJob, "  警告: 扩展 {$name} 没有配置版本");
            return true;
        }

        $successCount = 0;
        $totalVersions = count($versions);

        foreach ($versions as $version) {
            $this->updateJobLog($syncJob, "  下载 {$name}-{$version}");

            if ($this->downloadExtensionVersion($syncJob, $name, $version, $dataDir)) {
                $successCount++;
            }
        }

        $this->updateJobLog($syncJob, "  扩展 {$name} 完成: {$successCount}/{$totalVersions} 个版本");
        return $successCount > 0;
    }

    /**
     * 下载扩展版本
     *
     * @param SyncJob $syncJob 同步任务
     * @param string $name 扩展名
     * @param string $version 版本号
     * @param string $dataDir 数据目录
     * @return bool
     */
    protected function downloadExtensionVersion(SyncJob $syncJob, string $name, string $version, string $dataDir): bool
    {
        $filename = "{$name}-{$version}.tgz";
        $targetFile = $dataDir . '/' . $filename;

        // 检查文件是否已存在且有效
        if (file_exists($targetFile) && $this->validateExistingFile($targetFile, $name, $version)) {
            $this->updateJobLog($syncJob, "    文件已存在且有效: {$filename}");
            return true;
        }

        // 生成下载URL
        $downloadUrls = $this->getPeclDownloadUrls($name, $version);

        foreach ($downloadUrls as $index => $url) {
            $urlNumber = $index + 1;
            $totalUrls = count($downloadUrls);
            $this->updateJobLog($syncJob, "    尝试源 {$urlNumber}/{$totalUrls}: {$url}");

            try {
                if ($this->downloadFile($url, $targetFile)) {
                    // 验证PECL包
                    if ($this->validatePeclPackage($targetFile, $name, $version)) {
                        $this->updateJobLog($syncJob, "    {$name}-{$version} 下载并验证完成");
                        return true;
                    } else {
                        $this->updateJobLog($syncJob, "    包验证失败，尝试下一个源...");
                        if (file_exists($targetFile)) {
                            unlink($targetFile);
                        }
                    }
                } else {
                    $this->updateJobLog($syncJob, "    下载失败，尝试下一个源...");
                }
            } catch (\Exception $e) {
                $this->updateJobLog($syncJob, "    下载异常: " . $e->getMessage() . "，尝试下一个源...");
            }
        }

        $this->updateJobLog($syncJob, "    错误: {$name}-{$version} 所有源都下载失败");
        return false;
    }

    /**
     * 获取PECL下载URL列表
     *
     * @param string $name 扩展名
     * @param string $version 版本号
     * @return array
     */
    protected function getPeclDownloadUrls(string $name, string $version): array
    {
        $urls = [];

        // 主要源
        $urls[] = "https://pecl.php.net/get/{$name}-{$version}.tgz";

        // 备用源
        $urls[] = "https://github.com/php/pecl-{$name}/archive/{$version}.tar.gz";

        return $urls;
    }

    /**
     * 下载文件
     *
     * @param string $url 下载URL
     * @param string $destination 目标路径
     * @return bool
     */
    protected function downloadFile(string $url, string $destination): bool
    {
        try {
            $response = Http::timeout(300)->get($url);
            
            if ($response->successful()) {
                file_put_contents($destination, $response->body());
                return true;
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error("PECL文件下载失败", [
                'url' => $url,
                'destination' => $destination,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * 验证已存在的文件
     *
     * @param string $filePath 文件路径
     * @param string $name 扩展名
     * @param string $version 版本号
     * @return bool
     */
    protected function validateExistingFile(string $filePath, string $name, string $version): bool
    {
        // 检查文件大小
        $fileSize = filesize($filePath);
        if ($fileSize < 1024) { // 小于1KB
            return false;
        }

        // 检查文件格式
        return $this->validatePeclPackage($filePath, $name, $version);
    }

    /**
     * 验证PECL包
     *
     * @param string $filePath 文件路径
     * @param string $name 扩展名
     * @param string $version 版本号
     * @return bool
     */
    protected function validatePeclPackage(string $filePath, string $name, string $version): bool
    {
        // 基本文件检查
        if (!file_exists($filePath) || filesize($filePath) < 1024) {
            return false;
        }

        // 检查文件类型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        if (!in_array($mimeType, ['application/gzip', 'application/x-gzip', 'application/x-tar'])) {
            return false;
        }

        // TODO: 可以添加更详细的tgz内容验证
        return true;
    }

    /**
     * 获取扩展信息
     *
     * @param string $name 扩展名
     * @return array|null
     */
    public function getExtensionInfo(string $name): ?array
    {
        $cacheKey = "pecl_extension_info_{$name}";
        
        return $this->cacheService->remember($cacheKey, function () use ($name) {
            try {
                $response = Http::timeout(30)->get("https://pecl.php.net/rest/p/{$name}/info.xml");
                
                if ($response->successful()) {
                    $xml = simplexml_load_string($response->body());
                    
                    return [
                        'name' => (string) $xml->n,
                        'summary' => (string) $xml->s,
                        'description' => (string) $xml->d,
                        'license' => (string) $xml->l,
                        'category' => (string) $xml->ca,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("获取PECL扩展信息失败", [
                    'extension' => $name,
                    'error' => $e->getMessage()
                ]);
            }
            
            return null;
        }, 3600); // 缓存1小时
    }

    /**
     * 获取扩展版本列表
     *
     * @param string $name 扩展名
     * @return array
     */
    public function getExtensionVersions(string $name): array
    {
        $cacheKey = "pecl_extension_versions_{$name}";
        
        return $this->cacheService->remember($cacheKey, function () use ($name) {
            try {
                $response = Http::timeout(30)->get("https://pecl.php.net/rest/r/{$name}/allreleases.xml");
                
                if ($response->successful()) {
                    $xml = simplexml_load_string($response->body());
                    $versions = [];
                    
                    foreach ($xml->r as $release) {
                        $versions[] = [
                            'version' => (string) $release->v,
                            'stability' => (string) $release->s,
                            'date' => (string) $release->d,
                        ];
                    }
                    
                    return $versions;
                }
            } catch (\Exception $e) {
                Log::warning("获取PECL扩展版本失败", [
                    'extension' => $name,
                    'error' => $e->getMessage()
                ]);
            }
            
            return [];
        }, 3600); // 缓存1小时
    }

    /**
     * 确保目录存在
     *
     * @param string $directory 目录路径
     * @return void
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * 更新任务日志
     *
     * @param SyncJob $syncJob 同步任务
     * @param string $message 日志消息
     * @return void
     */
    protected function updateJobLog(SyncJob $syncJob, string $message): void
    {
        $syncJob->addLog($message);
    }
}
