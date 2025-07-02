<?php

namespace App\Services;

use App\Models\Mirror;
use App\Models\SyncJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * 同步服务
 * 
 * 负责处理镜像同步逻辑，包括PHP、PECL、扩展等不同类型的同步
 */
class SyncService
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
     * PHP镜像服务
     *
     * @var PhpMirrorService
     */
    protected $phpMirrorService;

    /**
     * PECL镜像服务
     *
     * @var PeclMirrorService
     */
    protected $peclMirrorService;

    /**
     * 扩展镜像服务
     *
     * @var ExtensionMirrorService
     */
    protected $extensionMirrorService;

    /**
     * 构造函数
     *
     * @param ConfigService $configService
     * @param CacheService $cacheService
     * @param PhpMirrorService $phpMirrorService
     * @param PeclMirrorService $peclMirrorService
     * @param ExtensionMirrorService $extensionMirrorService
     */
    public function __construct(
        ConfigService $configService,
        CacheService $cacheService,
        PhpMirrorService $phpMirrorService,
        PeclMirrorService $peclMirrorService,
        ExtensionMirrorService $extensionMirrorService
    ) {
        $this->configService = $configService;
        $this->cacheService = $cacheService;
        $this->phpMirrorService = $phpMirrorService;
        $this->peclMirrorService = $peclMirrorService;
        $this->extensionMirrorService = $extensionMirrorService;
    }

    /**
     * 执行同步任务
     *
     * @param SyncJob $syncJob 同步任务
     * @return bool
     */
    public function executeSyncJob(SyncJob $syncJob): bool
    {
        try {
            // 更新任务状态为运行中
            $syncJob->update([
                'status' => 'running',
                'started_at' => now(),
                'progress' => 0,
            ]);

            $mirrorType = $syncJob->mirror_type;

            Log::info("开始同步镜像", [
                'job_id' => $syncJob->id,
                'mirror_type' => $mirrorType
            ]);

            // 根据镜像类型执行不同的同步逻辑
            $result = match ($mirrorType) {
                'php' => $this->syncPhpMirror($syncJob),
                'pecl' => $this->syncPeclMirror($syncJob),
                'github' => $this->syncGithubMirror($syncJob),
                'composer' => $this->syncComposerMirror($syncJob),
                default => throw new \Exception("不支持的镜像类型: {$mirrorType}")
            };

            if ($result) {
                $syncJob->update([
                    'status' => 'completed',
                    'progress' => 100,
                    'completed_at' => now(),
                ]);

                Log::info("镜像同步完成", [
                    'job_id' => $syncJob->id,
                    'mirror_type' => $mirrorType
                ]);
            } else {
                throw new \Exception("同步失败");
            }

            return true;

        } catch (\Exception $e) {
            $syncJob->update([
                'status' => 'failed',
                'log' => $syncJob->log . "\n错误: " . $e->getMessage(),
            ]);

            Log::error("镜像同步失败", [
                'job_id' => $syncJob->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }



    /**
     * 同步PHP镜像
     *
     * @param SyncJob $syncJob 同步任务
     * @return bool
     */
    protected function syncPhpMirror(SyncJob $syncJob): bool
    {
        $this->updateJobLog($syncJob, "开始同步PHP源码...");

        try {
            // 获取PHP配置
            $config = config('mirror.php');
            if (!$config || !$config['enabled']) {
                $this->updateJobLog($syncJob, "PHP镜像已禁用，跳过同步");
                return true;
            }

            // 获取数据目录
            $dataDir = $this->configService->getDataDir() . '/php';
            $this->ensureDirectoryExists($dataDir);

            // 获取PHP版本配置
            $versions = $this->getPhpVersions();
            if (empty($versions)) {
                $this->updateJobLog($syncJob, "错误: 无法获取PHP版本配置");
                return false;
            }

            $this->updateJobLog($syncJob, "发现 " . count($versions) . " 个PHP版本需要同步");

            // 执行同步
            $successCount = 0;
            $totalVersions = count($versions);

            foreach ($versions as $index => $version) {
                $current = $index + 1;
                $this->updateJobLog($syncJob, "同步PHP版本: {$version} ({$current}/{$totalVersions})");

                if ($this->downloadPhpVersion($syncJob, $version, $dataDir, $config)) {
                    $successCount++;
                }

                // 更新进度
                $progress = (int)(($index + 1) / $totalVersions * 100);
                $syncJob->updateProgress($progress);
            }

            $this->updateJobLog($syncJob, "PHP同步完成，成功同步 {$successCount}/{$totalVersions} 个版本");
            return $successCount === $totalVersions;

        } catch (\Exception $e) {
            $this->updateJobLog($syncJob, "PHP同步异常: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 同步PECL镜像
     *
     * @param SyncJob $syncJob 同步任务
     * @return bool
     */
    protected function syncPeclMirror(SyncJob $syncJob): bool
    {
        $this->updateJobLog($syncJob, "开始同步PECL扩展...");

        try {
            // 获取PECL配置
            $config = config('mirror.pecl');
            if (!$config || !$config['enabled']) {
                $this->updateJobLog($syncJob, "PECL镜像已禁用，跳过同步");
                return true;
            }

            // 获取数据目录
            $dataDir = $this->configService->getDataDir() . '/pecl';
            $this->ensureDirectoryExists($dataDir);

            // 获取PECL扩展列表
            $extensions = $config['extensions'] ?? [];
            if (empty($extensions)) {
                $this->updateJobLog($syncJob, "错误: 无法获取PECL扩展配置");
                return false;
            }

            $this->updateJobLog($syncJob, "发现 " . count($extensions) . " 个PECL扩展需要同步");

            // 执行同步
            $successCount = 0;
            $totalExtensions = count($extensions);

            foreach ($extensions as $index => $extensionName) {
                $current = $index + 1;
                $this->updateJobLog($syncJob, "同步PECL扩展: {$extensionName} ({$current}/{$totalExtensions})");

                if ($this->syncPeclExtension($syncJob, $extensionName, $dataDir, $config)) {
                    $successCount++;
                }

                // 更新进度
                $progress = (int)(($index + 1) / $totalExtensions * 100);
                $syncJob->updateProgress($progress);
            }

            $this->updateJobLog($syncJob, "PECL同步完成，成功同步 {$successCount}/{$totalExtensions} 个扩展");
            return $successCount === $totalExtensions;

        } catch (\Exception $e) {
            $this->updateJobLog($syncJob, "PECL同步异常: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 同步GitHub扩展镜像
     *
     * @param SyncJob $syncJob 同步任务
     * @return bool
     */
    protected function syncGithubMirror(SyncJob $syncJob): bool
    {
        $this->updateJobLog($syncJob, "开始同步GitHub扩展...");

        try {
            // 获取GitHub扩展配置
            $config = config('mirror.extensions');
            if (!$config || !$config['enabled']) {
                $this->updateJobLog($syncJob, "GitHub扩展镜像已禁用，跳过同步");
                return true;
            }

            // 获取数据目录
            $dataDir = $this->configService->getDataDir() . '/extensions';
            $this->ensureDirectoryExists($dataDir);

            // 获取GitHub扩展列表
            $extensions = $config['github_extensions'] ?? [];
            if (empty($extensions)) {
                $this->updateJobLog($syncJob, "错误: 无法获取GitHub扩展配置");
                return false;
            }

            $this->updateJobLog($syncJob, "发现 " . count($extensions) . " 个GitHub扩展需要同步");

            // 执行同步
            $successCount = 0;
            $totalExtensions = count($extensions);

            foreach ($extensions as $index => $extensionName) {
                $current = $index + 1;
                $this->updateJobLog($syncJob, "同步GitHub扩展: {$extensionName} ({$current}/{$totalExtensions})");

                if ($this->syncGithubExtension($syncJob, $extensionName, $dataDir)) {
                    $successCount++;
                }

                // 更新进度
                $progress = (int)(($index + 1) / $totalExtensions * 100);
                $syncJob->updateProgress($progress);
            }

            $this->updateJobLog($syncJob, "GitHub扩展同步完成，成功同步 {$successCount}/{$totalExtensions} 个扩展");
            return $successCount === $totalExtensions;

        } catch (\Exception $e) {
            $this->updateJobLog($syncJob, "GitHub扩展同步异常: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 同步Composer镜像
     *
     * @param SyncJob $syncJob 同步任务
     * @return bool
     */
    protected function syncComposerMirror(SyncJob $syncJob): bool
    {
        $this->updateJobLog($syncJob, "开始同步Composer包...");

        try {
            // 获取Composer配置
            $config = config('mirror.composer');
            if (!$config || !$config['enabled']) {
                $this->updateJobLog($syncJob, "Composer镜像已禁用，跳过同步");
                return true;
            }

            // 获取数据目录
            $dataDir = $this->configService->getDataDir() . '/composer';
            $this->ensureDirectoryExists($dataDir);

            // 获取Composer版本列表
            $versions = $this->getComposerVersions();
            if (empty($versions)) {
                $this->updateJobLog($syncJob, "错误: 无法获取Composer版本配置");
                return false;
            }

            $this->updateJobLog($syncJob, "发现 " . count($versions) . " 个Composer版本需要同步");

            // 执行同步
            $successCount = 0;
            $totalVersions = count($versions);

            foreach ($versions as $index => $version) {
                $current = $index + 1;
                $this->updateJobLog($syncJob, "同步Composer版本: {$version} ({$current}/{$totalVersions})");

                if ($this->downloadComposerVersion($syncJob, $version, $dataDir, $config)) {
                    $successCount++;
                }

                // 更新进度
                $progress = (int)(($index + 1) / $totalVersions * 100);
                $syncJob->updateProgress($progress);
            }

            $this->updateJobLog($syncJob, "Composer同步完成，成功同步 {$successCount}/{$totalVersions} 个版本");
            return $successCount === $totalVersions;

        } catch (\Exception $e) {
            $this->updateJobLog($syncJob, "Composer同步异常: " . $e->getMessage());
            return false;
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
        $timestamp = now()->format('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}";

        $syncJob->update([
            'log' => $syncJob->log . "\n" . $logEntry
        ]);

        Log::info("同步日志", [
            'job_id' => $syncJob->id,
            'message' => $message
        ]);
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
     * 获取PHP版本列表
     *
     * @return array
     */
    protected function getPhpVersions(): array
    {
        // 从配置文件获取PHP版本
        $versionConfig = config_path('mirror/php/versions.php');
        if (file_exists($versionConfig)) {
            $config = require $versionConfig;
            $versions = $config['versions'] ?? [];

            // 将分组版本合并为单一数组
            $allVersions = [];
            foreach ($versions as $versionGroup) {
                if (is_array($versionGroup)) {
                    $allVersions = array_merge($allVersions, $versionGroup);
                }
            }
            return $allVersions;
        }

        // 如果没有版本配置文件，返回一些默认版本
        return [
            '8.3.21', '8.3.20', '8.3.19',
            '8.2.24', '8.2.23', '8.2.22',
            '8.1.30', '8.1.29', '8.1.28',
        ];
    }

    /**
     * 下载PHP版本
     *
     * @param SyncJob $syncJob 同步任务
     * @param string $version 版本号
     * @param string $dataDir 数据目录
     * @param array $config 配置
     * @return bool
     */
    protected function downloadPhpVersion(SyncJob $syncJob, string $version, string $dataDir, array $config): bool
    {
        $filename = "php-{$version}.tar.gz";
        $targetFile = $dataDir . '/' . $filename;

        // 检查文件是否已存在
        if (file_exists($targetFile) && $this->validatePhpFile($targetFile)) {
            $this->updateJobLog($syncJob, "  文件已存在且有效: {$filename}");
            return true;
        }

        // 生成下载URL
        $downloadUrls = $this->getPhpDownloadUrls($version, $config);

        foreach ($downloadUrls as $index => $url) {
            $urlNumber = $index + 1;
            $totalUrls = count($downloadUrls);
            $this->updateJobLog($syncJob, "  尝试源 {$urlNumber}/{$totalUrls}: {$url}");

            try {
                if (\App\Utils\FileDownloader::downloadFile($url, $targetFile, [
                    'min_size' => 5 * 1024 * 1024, // 5MB
                    'timeout' => 600,
                    'expected_type' => 'tar.gz',
                ])) {
                    if ($this->validatePhpFile($targetFile)) {
                        $this->updateJobLog($syncJob, "  PHP {$version} 下载并验证完成");
                        return true;
                    } else {
                        $this->updateJobLog($syncJob, "  源码包验证失败，尝试下一个源...");
                        if (file_exists($targetFile)) {
                            unlink($targetFile);
                        }
                    }
                } else {
                    $this->updateJobLog($syncJob, "  下载失败，尝试下一个源...");
                }
            } catch (\Exception $e) {
                $this->updateJobLog($syncJob, "  下载异常: " . $e->getMessage() . "，尝试下一个源...");
            }
        }

        $this->updateJobLog($syncJob, "  错误: PHP {$version} 所有源都下载失败");
        return false;
    }

    /**
     * 获取PHP下载URL列表
     *
     * @param string $version 版本号
     * @param array $config 配置
     * @return array
     */
    protected function getPhpDownloadUrls(string $version, array $config): array
    {
        $source = $config['source'] ?? 'https://www.php.net/distributions';
        $source = rtrim($source, '/');

        return [
            "{$source}/php-{$version}.tar.gz",
            "https://museum.php.net/php{$version[0]}/php-{$version}.tar.gz",
            "https://github.com/php/php-src/archive/php-{$version}.tar.gz",
        ];
    }

    /**
     * 验证PHP文件
     *
     * @param string $filePath 文件路径
     * @return bool
     */
    protected function validatePhpFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $fileSize = filesize($filePath);
        if ($fileSize < 5 * 1024 * 1024) { // 小于5MB
            return false;
        }

        // 检查是否为有效的gzip文件
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 3);
        fclose($handle);

        return $header === "\x1f\x8b\x08";
    }

    /**
     * 同步单个PECL扩展
     *
     * @param SyncJob $syncJob 同步任务
     * @param string $extensionName 扩展名
     * @param string $dataDir 数据目录
     * @param array $config 配置
     * @return bool
     */
    protected function syncPeclExtension(SyncJob $syncJob, string $extensionName, string $dataDir, array $config): bool
    {
        // 获取扩展版本配置
        $versions = $this->getPeclExtensionVersions($extensionName);
        if (empty($versions)) {
            $this->updateJobLog($syncJob, "  警告: 扩展 {$extensionName} 没有版本配置，跳过");
            return true;
        }

        $successCount = 0;
        foreach ($versions as $version) {
            if ($this->downloadPeclExtensionVersion($syncJob, $extensionName, $version, $dataDir, $config)) {
                $successCount++;
            }
        }

        $totalVersions = count($versions);
        $this->updateJobLog($syncJob, "  扩展 {$extensionName} 同步完成: {$successCount}/{$totalVersions}");

        return $successCount > 0; // 至少成功一个版本就算成功
    }

    /**
     * 获取PECL扩展版本列表
     *
     * @param string $extensionName 扩展名
     * @return array
     */
    protected function getPeclExtensionVersions(string $extensionName): array
    {
        $configFile = config_path("mirror/pecl/{$extensionName}.php");
        if (file_exists($configFile)) {
            $config = require $configFile;
            return $config['versions'] ?? [];
        }

        // 如果没有配置文件，返回一些常见版本
        $defaultVersions = [
            'redis' => ['5.3.7', '6.0.2'],
            'swoole' => ['4.8.13', '5.0.3'],
            'xdebug' => ['3.2.2', '3.3.1'],
            'imagick' => ['3.7.0'],
            'memcached' => ['3.2.0'],
        ];

        return $defaultVersions[$extensionName] ?? [];
    }

    /**
     * 下载PECL扩展版本
     *
     * @param SyncJob $syncJob 同步任务
     * @param string $extensionName 扩展名
     * @param string $version 版本号
     * @param string $dataDir 数据目录
     * @param array $config 配置
     * @return bool
     */
    protected function downloadPeclExtensionVersion(SyncJob $syncJob, string $extensionName, string $version, string $dataDir, array $config): bool
    {
        $filename = "{$extensionName}-{$version}.tgz";
        $targetFile = $dataDir . '/' . $filename;

        // 检查文件是否已存在
        if (file_exists($targetFile) && $this->validatePeclFile($targetFile)) {
            $this->updateJobLog($syncJob, "    文件已存在且有效: {$filename}");
            return true;
        }

        // 生成下载URL
        $downloadUrls = $this->getPeclDownloadUrls($extensionName, $version, $config);

        foreach ($downloadUrls as $index => $url) {
            $urlNumber = $index + 1;
            $totalUrls = count($downloadUrls);
            $this->updateJobLog($syncJob, "    尝试源 {$urlNumber}/{$totalUrls}: {$url}");

            try {
                if (\App\Utils\FileDownloader::downloadFile($url, $targetFile, [
                    'min_size' => 10 * 1024, // 10KB
                    'timeout' => 300,
                    'expected_type' => 'tgz',
                ])) {
                    if ($this->validatePeclFile($targetFile)) {
                        $this->updateJobLog($syncJob, "    {$extensionName}-{$version} 下载并验证完成");
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

        $this->updateJobLog($syncJob, "    错误: {$extensionName}-{$version} 所有源都下载失败");
        return false;
    }

    /**
     * 获取PECL下载URL列表
     *
     * @param string $extensionName 扩展名
     * @param string $version 版本号
     * @param array $config 配置
     * @return array
     */
    protected function getPeclDownloadUrls(string $extensionName, string $version, array $config): array
    {
        $source = $config['source'] ?? 'https://pecl.php.net/get';
        $source = rtrim($source, '/');

        return [
            "{$source}/{$extensionName}-{$version}.tgz",
            "https://pecl.php.net/get/{$extensionName}-{$version}.tgz",
        ];
    }

    /**
     * 验证PECL文件
     *
     * @param string $filePath 文件路径
     * @return bool
     */
    protected function validatePeclFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $fileSize = filesize($filePath);
        if ($fileSize < 10 * 1024) { // 小于10KB
            return false;
        }

        // 检查是否为有效的gzip文件
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 3);
        fclose($handle);

        return $header === "\x1f\x8b\x08";
    }

    /**
     * 同步单个GitHub扩展
     *
     * @param SyncJob $syncJob 同步任务
     * @param string $extensionName 扩展名
     * @param string $dataDir 数据目录
     * @return bool
     */
    protected function syncGithubExtension(SyncJob $syncJob, string $extensionName, string $dataDir): bool
    {
        // 获取扩展版本配置
        $versions = $this->getGithubExtensionVersions($extensionName);
        if (empty($versions)) {
            $this->updateJobLog($syncJob, "  警告: 扩展 {$extensionName} 没有版本配置，跳过");
            return true;
        }

        $successCount = 0;
        foreach ($versions as $version) {
            if ($this->downloadGithubExtensionVersion($syncJob, $extensionName, $version, $dataDir)) {
                $successCount++;
            }
        }

        $totalVersions = count($versions);
        $this->updateJobLog($syncJob, "  扩展 {$extensionName} 同步完成: {$successCount}/{$totalVersions}");

        return $successCount > 0;
    }

    /**
     * 获取GitHub扩展版本列表
     *
     * @param string $extensionName 扩展名
     * @return array
     */
    protected function getGithubExtensionVersions(string $extensionName): array
    {
        $configFile = config_path("mirror/extensions/{$extensionName}.php");
        if (file_exists($configFile)) {
            $config = require $configFile;
            return $config['versions'] ?? [];
        }

        // 默认版本
        $defaultVersions = [
            'swoole' => ['v4.8.13', 'v5.0.3'],
            'imagick' => ['3.7.0'],
            'xdebug' => ['3.2.2', '3.3.1'],
        ];

        return $defaultVersions[$extensionName] ?? [];
    }

    /**
     * 下载GitHub扩展版本
     *
     * @param SyncJob $syncJob 同步任务
     * @param string $extensionName 扩展名
     * @param string $version 版本号
     * @param string $dataDir 数据目录
     * @return bool
     */
    protected function downloadGithubExtensionVersion(SyncJob $syncJob, string $extensionName, string $version, string $dataDir): bool
    {
        $filename = "{$extensionName}-{$version}.tar.gz";
        $targetFile = $dataDir . '/' . $filename;

        // 检查文件是否已存在
        if (file_exists($targetFile) && $this->validateGithubFile($targetFile)) {
            $this->updateJobLog($syncJob, "    文件已存在且有效: {$filename}");
            return true;
        }

        // 生成下载URL
        $downloadUrls = $this->getGithubDownloadUrls($extensionName, $version);

        foreach ($downloadUrls as $index => $url) {
            $urlNumber = $index + 1;
            $totalUrls = count($downloadUrls);
            $this->updateJobLog($syncJob, "    尝试源 {$urlNumber}/{$totalUrls}: {$url}");

            try {
                if (\App\Utils\FileDownloader::downloadFile($url, $targetFile, [
                    'min_size' => 50 * 1024, // 50KB
                    'timeout' => 300,
                    'expected_type' => 'tar.gz',
                ])) {
                    if ($this->validateGithubFile($targetFile)) {
                        $this->updateJobLog($syncJob, "    {$extensionName}-{$version} 下载并验证完成");
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

        $this->updateJobLog($syncJob, "    错误: {$extensionName}-{$version} 所有源都下载失败");
        return false;
    }

    /**
     * 获取GitHub下载URL列表
     *
     * @param string $extensionName 扩展名
     * @param string $version 版本号
     * @return array
     */
    protected function getGithubDownloadUrls(string $extensionName, string $version): array
    {
        // GitHub仓库映射
        $repoMap = [
            'swoole' => 'swoole/swoole-src',
            'imagick' => 'Imagick/imagick',
            'xdebug' => 'xdebug/xdebug',
        ];

        $repo = $repoMap[$extensionName] ?? "{$extensionName}/{$extensionName}";

        return [
            "https://github.com/{$repo}/archive/refs/tags/{$version}.tar.gz",
            "https://github.com/{$repo}/archive/{$version}.tar.gz",
        ];
    }

    /**
     * 验证GitHub文件
     *
     * @param string $filePath 文件路径
     * @return bool
     */
    protected function validateGithubFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $fileSize = filesize($filePath);
        if ($fileSize < 50 * 1024) { // 小于50KB
            return false;
        }

        // 检查是否为有效的gzip文件
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 3);
        fclose($handle);

        return $header === "\x1f\x8b\x08";
    }

    /**
     * 获取Composer版本列表
     *
     * @return array
     */
    protected function getComposerVersions(): array
    {
        $configFile = config_path('mirror/composer/versions.php');
        if (file_exists($configFile)) {
            $config = require $configFile;
            return $config['versions'] ?? [];
        }

        // 默认版本
        return [
            'stable',
            '2.7.9',
            '2.8.9',
            '2.6.6',
        ];
    }

    /**
     * 下载Composer版本
     *
     * @param SyncJob $syncJob 同步任务
     * @param string $version 版本号
     * @param string $dataDir 数据目录
     * @param array $config 配置
     * @return bool
     */
    protected function downloadComposerVersion(SyncJob $syncJob, string $version, string $dataDir, array $config): bool
    {
        $filename = "composer-{$version}.phar";
        $targetFile = $dataDir . '/' . $filename;

        // 检查文件是否已存在
        if (file_exists($targetFile) && $this->validateComposerFile($targetFile)) {
            $this->updateJobLog($syncJob, "  文件已存在且有效: {$filename}");
            return true;
        }

        // 生成下载URL
        $downloadUrls = $this->getComposerDownloadUrls($version, $config);

        foreach ($downloadUrls as $index => $url) {
            $urlNumber = $index + 1;
            $totalUrls = count($downloadUrls);
            $this->updateJobLog($syncJob, "  尝试源 {$urlNumber}/{$totalUrls}: {$url}");

            try {
                if (\App\Utils\FileDownloader::downloadFile($url, $targetFile, [
                    'min_size' => 100 * 1024, // 100KB
                    'timeout' => 300,
                    'expected_type' => 'phar',
                ])) {
                    if ($this->validateComposerFile($targetFile)) {
                        $this->updateJobLog($syncJob, "  Composer {$version} 下载并验证完成");
                        return true;
                    } else {
                        $this->updateJobLog($syncJob, "  文件验证失败，尝试下一个源...");
                        if (file_exists($targetFile)) {
                            unlink($targetFile);
                        }
                    }
                } else {
                    $this->updateJobLog($syncJob, "  下载失败，尝试下一个源...");
                }
            } catch (\Exception $e) {
                $this->updateJobLog($syncJob, "  下载异常: " . $e->getMessage() . "，尝试下一个源...");
            }
        }

        $this->updateJobLog($syncJob, "  错误: Composer {$version} 所有源都下载失败");
        return false;
    }

    /**
     * 获取Composer下载URL列表
     *
     * @param string $version 版本号
     * @param array $config 配置
     * @return array
     */
    protected function getComposerDownloadUrls(string $version, array $config): array
    {
        $source = $config['source'] ?? 'https://getcomposer.org/download';
        $source = rtrim($source, '/');

        if ($version === 'stable') {
            return [
                "https://getcomposer.org/composer-stable.phar",
                "https://getcomposer.org/composer.phar",
            ];
        }

        return [
            "{$source}/{$version}/composer.phar",
            "https://getcomposer.org/download/{$version}/composer.phar",
        ];
    }

    /**
     * 验证Composer文件
     *
     * @param string $filePath 文件路径
     * @return bool
     */
    protected function validateComposerFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $fileSize = filesize($filePath);
        if ($fileSize < 100 * 1024) { // 小于100KB
            return false;
        }

        // 检查是否为有效的Phar文件
        try {
            $phar = new \Phar($filePath);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
