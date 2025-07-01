<?php

namespace App\Services;

use App\Models\Mirror;
use App\Models\SyncJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * 扩展镜像服务
 * 
 * 负责GitHub扩展的同步和管理
 */
class ExtensionMirrorService
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
     * 同步GitHub扩展镜像
     *
     * @param Mirror $mirror 镜像对象
     * @param SyncJob $syncJob 同步任务
     * @return bool
     */
    public function sync(Mirror $mirror, SyncJob $syncJob): bool
    {
        $this->updateJobLog($syncJob, "开始同步GitHub扩展...");

        $config = $mirror->config;
        $extensions = $config['github_extensions'] ?? [];

        if (empty($extensions)) {
            $this->updateJobLog($syncJob, "错误: GitHub扩展配置为空");
            return false;
        }

        $baseDir = $this->configService->getDataDir();
        $successCount = 0;
        $totalExtensions = count($extensions);

        foreach ($extensions as $index => $extension) {
            $this->updateJobLog($syncJob, "同步GitHub扩展: {$extension['name']} ({$index + 1}/{$totalExtensions})");

            if ($this->syncExtension($syncJob, $extension, $baseDir)) {
                $successCount++;
            }

            // 更新进度
            $progress = (int)(($index + 1) / $totalExtensions * 100);
            $syncJob->updateProgress($progress);
        }

        $this->updateJobLog($syncJob, "GitHub扩展同步完成，成功同步 {$successCount}/{$totalExtensions} 个扩展");
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
        $this->updateJobLog($syncJob, "同步GitHub指定扩展: {$extensionName}");

        $config = $mirror->config;
        $extensions = $config['github_extensions'] ?? [];

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

        $baseDir = $this->configService->getDataDir();
        return $this->syncExtension($syncJob, $targetExtension, $baseDir);
    }

    /**
     * 同步单个扩展
     *
     * @param SyncJob $syncJob 同步任务
     * @param array $extension 扩展配置
     * @param string $baseDir 基础目录
     * @return bool
     */
    protected function syncExtension(SyncJob $syncJob, array $extension, string $baseDir): bool
    {
        $name = $extension['name'];
        $repo = $extension['repo'];
        $versions = $extension['versions'] ?? [];

        if (empty($versions)) {
            $this->updateJobLog($syncJob, "  警告: 扩展 {$name} 没有配置版本");
            return true;
        }

        // 解析GitHub信息
        $githubInfo = $this->parseGithubRepo($repo);
        if (!$githubInfo) {
            $this->updateJobLog($syncJob, "  错误: 无法解析GitHub仓库信息: {$repo}");
            return false;
        }

        // 创建目录结构
        $dataDir = $baseDir . '/github/' . $githubInfo['owner'] . '/' . $githubInfo['repo'];
        $this->ensureDirectoryExists($dataDir);

        $successCount = 0;
        $totalVersions = count($versions);

        foreach ($versions as $version) {
            $this->updateJobLog($syncJob, "  下载 {$name}-{$version}");

            if ($this->downloadExtensionVersion($syncJob, $extension, $version, $dataDir)) {
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
     * @param array $extension 扩展配置
     * @param string $version 版本号
     * @param string $dataDir 数据目录
     * @return bool
     */
    protected function downloadExtensionVersion(SyncJob $syncJob, array $extension, string $version, string $dataDir): bool
    {
        $name = $extension['name'];
        $repo = $extension['repo'];
        $filename = "{$name}-{$version}.tar.gz";
        $targetFile = $dataDir . '/' . $filename;

        // 检查文件是否已存在且有效
        if (file_exists($targetFile) && $this->validateExistingFile($targetFile, $name, $version)) {
            $this->updateJobLog($syncJob, "    文件已存在且有效: {$filename}");
            return true;
        }

        // 生成下载URL
        $downloadUrls = $this->getGithubDownloadUrls($repo, $version);

        foreach ($downloadUrls as $index => $url) {
            $urlNumber = $index + 1;
            $totalUrls = count($downloadUrls);
            $this->updateJobLog($syncJob, "    尝试源 {$urlNumber}/{$totalUrls}: {$url}");

            try {
                if ($this->downloadFile($url, $targetFile)) {
                    // 验证GitHub扩展包
                    if ($this->validateGithubExtensionPackage($targetFile, $name, $version)) {
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
     * 解析GitHub仓库信息
     *
     * @param string $repo 仓库路径
     * @return array|null
     */
    protected function parseGithubRepo(string $repo): ?array
    {
        if (preg_match('/^([^\/]+)\/([^\/]+)$/', $repo, $matches)) {
            return [
                'owner' => $matches[1],
                'repo' => $matches[2],
            ];
        }

        return null;
    }

    /**
     * 获取GitHub下载URL列表
     *
     * @param string $repo 仓库路径
     * @param string $version 版本号
     * @return array
     */
    protected function getGithubDownloadUrls(string $repo, string $version): array
    {
        $urls = [];

        // 主要源 - GitHub releases
        $urls[] = "https://github.com/{$repo}/archive/refs/tags/{$version}.tar.gz";
        $urls[] = "https://github.com/{$repo}/archive/refs/tags/v{$version}.tar.gz";

        // 备用源 - GitHub archive
        $urls[] = "https://github.com/{$repo}/archive/{$version}.tar.gz";

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
            Log::error("GitHub扩展文件下载失败", [
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
        if ($fileSize < 1024 * 50) { // 小于50KB
            return false;
        }

        // 检查文件格式
        return $this->validateGithubExtensionPackage($filePath, $name, $version);
    }

    /**
     * 验证GitHub扩展包
     *
     * @param string $filePath 文件路径
     * @param string $name 扩展名
     * @param string $version 版本号
     * @return bool
     */
    protected function validateGithubExtensionPackage(string $filePath, string $name, string $version): bool
    {
        // 基本文件检查
        if (!file_exists($filePath) || filesize($filePath) < 1024 * 50) {
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
     * 获取GitHub仓库信息
     *
     * @param string $repo 仓库路径
     * @return array|null
     */
    public function getRepositoryInfo(string $repo): ?array
    {
        $cacheKey = "github_repo_info_{$repo}";
        
        return $this->cacheService->remember($cacheKey, function () use ($repo) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'PVM-Mirror/1.0'])
                    ->get("https://api.github.com/repos/{$repo}");
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    return [
                        'name' => $data['name'],
                        'full_name' => $data['full_name'],
                        'description' => $data['description'],
                        'language' => $data['language'],
                        'stars' => $data['stargazers_count'],
                        'forks' => $data['forks_count'],
                        'updated_at' => $data['updated_at'],
                        'default_branch' => $data['default_branch'],
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("获取GitHub仓库信息失败", [
                    'repo' => $repo,
                    'error' => $e->getMessage()
                ]);
            }
            
            return null;
        }, 3600); // 缓存1小时
    }

    /**
     * 获取GitHub仓库版本列表
     *
     * @param string $repo 仓库路径
     * @return array
     */
    public function getRepositoryVersions(string $repo): array
    {
        $cacheKey = "github_repo_versions_{$repo}";
        
        return $this->cacheService->remember($cacheKey, function () use ($repo) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders(['User-Agent' => 'PVM-Mirror/1.0'])
                    ->get("https://api.github.com/repos/{$repo}/tags");
                
                if ($response->successful()) {
                    $tags = $response->json();
                    $versions = [];
                    
                    foreach ($tags as $tag) {
                        $versions[] = [
                            'name' => $tag['name'],
                            'commit' => $tag['commit']['sha'],
                            'tarball_url' => $tag['tarball_url'],
                            'zipball_url' => $tag['zipball_url'],
                        ];
                    }
                    
                    return $versions;
                }
            } catch (\Exception $e) {
                Log::warning("获取GitHub仓库版本失败", [
                    'repo' => $repo,
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
