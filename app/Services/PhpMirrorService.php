<?php

namespace App\Services;

use App\Models\Mirror;
use App\Models\SyncJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * PHP镜像服务
 * 
 * 负责PHP源码包的同步和管理
 */
class PhpMirrorService
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
     * 同步PHP镜像
     *
     * @param Mirror $mirror 镜像对象
     * @param SyncJob $syncJob 同步任务
     * @return bool
     */
    public function sync(Mirror $mirror, SyncJob $syncJob): bool
    {
        $this->updateJobLog($syncJob, "开始同步PHP源码包...");

        $config = $mirror->config;
        $source = $config['source'] ?? 'https://www.php.net/distributions/';
        $versions = $config['versions'] ?? [];

        if (empty($versions)) {
            $this->updateJobLog($syncJob, "错误: PHP版本配置为空");
            return false;
        }

        $dataDir = $this->configService->getDataDir() . '/php';
        $this->ensureDirectoryExists($dataDir);

        // 预检查阶段
        $this->updateJobLog($syncJob, "=== 预检查阶段 ===");
        $downloadPlan = $this->collectVersionDownloadPlan($source, $dataDir, $versions);
        $this->showDownloadPlan($syncJob, $downloadPlan);

        // 下载阶段
        $this->updateJobLog($syncJob, "=== 下载阶段 ===");
        return $this->executeDownloadPlan($syncJob, $downloadPlan);
    }

    /**
     * 同步指定版本的PHP源码包
     *
     * @param Mirror $mirror 镜像对象
     * @param SyncJob $syncJob 同步任务
     * @param string $majorVersion 主版本号
     * @return bool
     */
    public function syncVersion(Mirror $mirror, SyncJob $syncJob, string $majorVersion): bool
    {
        $this->updateJobLog($syncJob, "同步PHP指定版本: {$majorVersion}");

        $config = $mirror->config;
        $versionGroups = $config['versions'] ?? [];

        if (!isset($versionGroups[$majorVersion])) {
            $this->updateJobLog($syncJob, "错误: 版本 {$majorVersion} 不在配置的版本列表中");
            $this->updateJobLog($syncJob, "可用版本: " . implode(', ', array_keys($versionGroups)));
            return false;
        }

        $versions = $versionGroups[$majorVersion];
        $source = $config['source'] ?? 'https://www.php.net/distributions/';
        $dataDir = $this->configService->getDataDir() . '/php';
        $this->ensureDirectoryExists($dataDir);

        // 预检查和下载
        $downloadPlan = $this->collectVersionDownloadPlan($source, $dataDir, $versions);
        return $this->executeDownloadPlan($syncJob, $downloadPlan);
    }

    /**
     * 收集版本下载计划
     *
     * @param string $source 源URL
     * @param string $dataDir 数据目录
     * @param array $versions 版本列表
     * @return array
     */
    protected function collectVersionDownloadPlan(string $source, string $dataDir, array $versions): array
    {
        $plan = [];

        foreach ($versions as $version) {
            $filename = "php-{$version}.tar.gz";
            $sourceUrl = rtrim($source, '/') . '/' . $filename;
            $targetFile = $dataDir . '/' . $filename;

            $exists = file_exists($targetFile);
            $fileSize = $exists ? filesize($targetFile) : 0;
            $isValid = $exists ? $this->validateExistingFile($targetFile, $version) : false;

            // 生成多个下载URL
            $downloadUrls = $this->getPhpDownloadUrls($version, $sourceUrl);

            $plan[] = [
                'version' => $version,
                'filename' => $filename,
                'source_url' => $sourceUrl,
                'download_urls' => $downloadUrls,
                'target_file' => $targetFile,
                'exists' => $exists,
                'is_valid' => $isValid,
                'file_size' => $fileSize,
                'estimated_size' => 20 * 1024 * 1024, // 估计20MB
                'needs_download' => !$exists || !$isValid
            ];
        }

        return $plan;
    }

    /**
     * 获取PHP下载URL列表
     *
     * @param string $version 版本号
     * @param string $primaryUrl 主要URL
     * @return array
     */
    protected function getPhpDownloadUrls(string $version, string $primaryUrl): array
    {
        $urls = [];

        // 主要源
        $urls[] = $primaryUrl;

        // 镜像源
        $urls[] = "https://museum.php.net/php{$version[0]}/php-{$version}.tar.gz";
        $urls[] = "https://github.com/php/php-src/archive/php-{$version}.tar.gz";

        return $urls;
    }

    /**
     * 显示下载计划
     *
     * @param SyncJob $syncJob 同步任务
     * @param array $downloadPlan 下载计划
     * @return void
     */
    protected function showDownloadPlan(SyncJob $syncJob, array $downloadPlan): void
    {
        $needsDownload = array_filter($downloadPlan, fn($item) => $item['needs_download']);
        $totalSize = array_sum(array_column($needsDownload, 'estimated_size'));

        $this->updateJobLog($syncJob, sprintf(
            "需要下载 %d 个文件，预计大小: %s",
            count($needsDownload),
            $this->formatBytes($totalSize)
        ));

        foreach ($downloadPlan as $item) {
            if ($item['needs_download']) {
                $this->updateJobLog($syncJob, "  待下载: {$item['filename']}");
            } else {
                $this->updateJobLog($syncJob, "  已存在: {$item['filename']} ({$this->formatBytes($item['file_size'])})");
            }
        }
    }

    /**
     * 执行下载计划
     *
     * @param SyncJob $syncJob 同步任务
     * @param array $downloadPlan 下载计划
     * @return bool
     */
    protected function executeDownloadPlan(SyncJob $syncJob, array $downloadPlan): bool
    {
        $needsDownload = array_filter($downloadPlan, fn($item) => $item['needs_download']);
        $totalItems = count($needsDownload);
        $successCount = 0;

        foreach ($needsDownload as $index => $item) {
            $current = $index + 1;
            $this->updateJobLog($syncJob, "下载 PHP {$item['version']} ({$current}/{$totalItems})");

            if ($this->downloadPhpVersion($syncJob, $item)) {
                $successCount++;
            }

            // 更新进度
            $progress = (int)(($index + 1) / $totalItems * 100);
            $syncJob->updateProgress($progress);
        }

        $this->updateJobLog($syncJob, "PHP同步完成，成功下载 {$successCount}/{$totalItems} 个版本");
        return $successCount === $totalItems;
    }

    /**
     * 下载PHP版本
     *
     * @param SyncJob $syncJob 同步任务
     * @param array $item 下载项
     * @return bool
     */
    protected function downloadPhpVersion(SyncJob $syncJob, array $item): bool
    {
        $downloadUrls = $item['download_urls'];

        foreach ($downloadUrls as $index => $url) {
            $urlNumber = $index + 1;
            $totalUrls = count($downloadUrls);
            $this->updateJobLog($syncJob, "  尝试源 {$urlNumber}/{$totalUrls}: {$url}");

            try {
                if ($this->downloadFile($url, $item['target_file'])) {
                    // 验证PHP源码包
                    if ($this->validatePhpSourcePackage($item['target_file'], $item['version'])) {
                        $this->updateJobLog($syncJob, "  PHP {$item['version']} 下载并验证完成");
                        return true;
                    } else {
                        $this->updateJobLog($syncJob, "  源码包验证失败，尝试下一个源...");
                        if (file_exists($item['target_file'])) {
                            unlink($item['target_file']);
                        }
                    }
                } else {
                    $this->updateJobLog($syncJob, "  下载失败，尝试下一个源...");
                }
            } catch (\Exception $e) {
                $this->updateJobLog($syncJob, "  下载异常: " . $e->getMessage() . "，尝试下一个源...");
            }
        }

        $this->updateJobLog($syncJob, "  错误: PHP {$item['version']} 所有源都下载失败");
        return false;
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
            Log::error("PHP文件下载失败", [
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
     * @param string $version 版本号
     * @return bool
     */
    protected function validateExistingFile(string $filePath, string $version): bool
    {
        // 检查文件大小
        $fileSize = filesize($filePath);
        if ($fileSize < 1024 * 1024) { // 小于1MB
            return false;
        }

        // 检查文件格式
        return $this->validatePhpSourcePackage($filePath, $version);
    }

    /**
     * 验证PHP源码包
     *
     * @param string $filePath 文件路径
     * @param string $version 版本号
     * @return bool
     */
    protected function validatePhpSourcePackage(string $filePath, string $version): bool
    {
        // 基本文件检查
        if (!file_exists($filePath) || filesize($filePath) < 1024 * 1024) {
            return false;
        }

        // 检查文件类型
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        if (!in_array($mimeType, ['application/gzip', 'application/x-gzip', 'application/x-tar'])) {
            return false;
        }

        // TODO: 可以添加更详细的tar.gz内容验证
        return true;
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

    /**
     * 格式化字节大小
     *
     * @param int $bytes 字节数
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
