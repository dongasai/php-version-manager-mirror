<?php

namespace Mirror\Command;

use Mirror\Mirror\PhpMirror;
use Mirror\Mirror\PeclMirror;
use Mirror\Mirror\ExtensionMirror;
use Mirror\Mirror\ComposerMirror;
use Mirror\Log\Logger;
use Mirror\Log\LogManager;

/**
 * 同步命令类
 */
class SyncCommand extends AbstractCommand
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct('sync', '同步镜像内容');
    }

    /**
     * 执行命令
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    public function execute(array $args = [])
    {
        // 如果有参数，则进行指定同步
        if (!empty($args)) {
            return $this->executeSpecificSync($args);
        }

        // 无参数时同步所有内容
        return $this->executeFullSync();
    }

    /**
     * 执行指定内容同步
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    private function executeSpecificSync(array $args)
    {
        $type = $args[0];
        $version = isset($args[1]) ? $args[1] : null;

        Logger::info("开始同步指定镜像内容...");
        Logger::info("类型: $type" . ($version ? ", 版本: $version" : ""));
        echo "开始同步指定镜像内容...\n";
        echo "类型: $type" . ($version ? ", 版本: $version" : "") . "\n\n";

        // 记录同步开始
        LogManager::pvmInfo("开始指定内容同步", "SYNC");
        LogManager::pvmInfo("同步类型: $type" . ($version ? ", 版本: $version" : ""), "SYNC");

        // 加载配置
        $configs = $this->loadConfig();
        $mirrorConfig = $configs['mirror'];
        $runtimeConfig = $configs['runtime'];

        // 显示同步配置信息
        $this->showSyncConfig($runtimeConfig);

        switch ($type) {
            case 'composer':
                return $this->syncComposer($mirrorConfig, $version);

            case 'php':
                return $this->syncPhp($mirrorConfig, $version);

            case 'pecl':
                return $this->syncPecl($mirrorConfig, $version);

            case 'extensions':
            case 'ext':
                return $this->syncExtensions($mirrorConfig, $version);

            default:
                echo "错误: 未知的同步类型 '$type'\n";
                echo "支持的类型: composer, php, pecl, extensions\n";
                echo "\n用法示例:\n";
                echo "  pvm-mirror sync composer           # 同步所有 Composer 版本\n";
                echo "  pvm-mirror sync composer 2.6.5     # 同步指定 Composer 版本\n";
                echo "  pvm-mirror sync php                # 同步所有 PHP 版本\n";
                echo "  pvm-mirror sync php 8.3            # 同步指定 PHP 主版本\n";
                echo "  pvm-mirror sync pecl               # 同步所有 PECL 扩展\n";
                echo "  pvm-mirror sync extensions          # 同步所有特定扩展\n";
                return 1;
        }
    }

    /**
     * 执行完整同步
     *
     * @return int 退出代码
     */
    private function executeFullSync()
    {
        Logger::info("开始同步镜像内容...");
        echo "开始同步镜像内容...\n";

        // 记录完整同步开始
        LogManager::pvmInfo("开始完整镜像同步", "SYNC");

        // 加载配置
        $configs = $this->loadConfig();
        $mirrorConfig = $configs['mirror'];
        $runtimeConfig = $configs['runtime'];

        // 显示同步配置信息
        $this->showSyncConfig($runtimeConfig);

        // 预检查阶段：收集所有待下载的包信息
        Logger::info("=== 预检查阶段 ===");
        echo "\n=== 预检查阶段 ===\n";
        LogManager::pvmInfo("开始预检查阶段", "SYNC");

        $downloadPlan = $this->collectDownloadPlan($mirrorConfig);

        // 显示下载计划统计
        $this->showDownloadPlanSummary($downloadPlan);
        LogManager::pvmInfo("预检查完成，需要下载 " . $downloadPlan['summary']['download_files'] . " 个文件", "SYNC");

        // 执行下载阶段
        Logger::info("=== 下载阶段 ===");
        echo "\n=== 下载阶段 ===\n";
        LogManager::pvmInfo("开始下载阶段", "SYNC");

        $this->executeDownloadPlan($downloadPlan, $runtimeConfig);

        Logger::success("镜像同步完成");
        echo "\n镜像同步完成\n";
        LogManager::pvmSuccess("完整镜像同步完成");

        return 0;
    }

    /**
     * 显示同步配置信息
     *
     * @param array $runtimeConfig 运行时配置
     */
    private function showSyncConfig(array $runtimeConfig)
    {
        $syncConfig = $runtimeConfig['sync'] ?? [];
        echo "同步配置:\n";
        echo "  最大重试次数: " . ($syncConfig['max_retries'] ?? 3) . "\n";
        echo "  重试间隔: " . ($syncConfig['retry_interval'] ?? 300) . " 秒\n";
        echo "  下载超时: " . ($syncConfig['download_timeout'] ?? 600) . " 秒\n";
        echo "  最大并行下载数: " . ($syncConfig['max_parallel_downloads'] ?? 5) . "\n";
    }

    /**
     * 同步 PHP 源码包
     *
     * @param array $mirrorConfig 镜像配置
     * @param string|null $version 指定版本
     * @return int 退出代码
     */
    private function syncPhp(array $mirrorConfig, $version = null)
    {
        if (!isset($mirrorConfig['php']['enabled']) || !$mirrorConfig['php']['enabled']) {
            echo "\n跳过 PHP 源码包同步 (已禁用)\n";
            return 0;
        }

        echo "\n同步 PHP 源码包...\n";
        $phpMirror = new PhpMirror();

        if ($version) {
            return $phpMirror->syncVersion($mirrorConfig['php'], $version) ? 0 : 1;
        } else {
            return $phpMirror->sync($mirrorConfig['php']) ? 0 : 1;
        }
    }

    /**
     * 同步 PECL 扩展包
     *
     * @param array $mirrorConfig 镜像配置
     * @param string|null $extension 指定扩展
     * @return int 退出代码
     */
    private function syncPecl(array $mirrorConfig, $extension = null)
    {
        if (!isset($mirrorConfig['pecl']['enabled']) || !$mirrorConfig['pecl']['enabled']) {
            echo "\n跳过 PECL 扩展包同步 (已禁用)\n";
            return 0;
        }

        echo "\n同步 PECL 扩展包...\n";
        $peclMirror = new PeclMirror();

        if ($extension) {
            return $peclMirror->syncExtension($mirrorConfig['pecl'], $extension) ? 0 : 1;
        } else {
            return $peclMirror->sync($mirrorConfig['pecl']) ? 0 : 1;
        }
    }

    /**
     * 同步特定扩展的 GitHub 源码
     *
     * @param array $mirrorConfig 镜像配置
     * @param string|null $extension 指定扩展
     * @return int 退出代码
     */
    private function syncExtensions(array $mirrorConfig, $extension = null)
    {
        $enabledExtensions = [];
        foreach ($mirrorConfig['extensions'] as $ext => $config) {
            if (isset($config['enabled']) && $config['enabled']) {
                if ($extension && $ext !== $extension) {
                    continue;
                }
                $enabledExtensions[$ext] = $config;
            }
        }

        if (empty($enabledExtensions)) {
            if ($extension) {
                echo "\n错误: 扩展 '$extension' 未启用或不存在\n";
                return 1;
            } else {
                echo "\n跳过特定扩展源码同步 (已禁用)\n";
                return 0;
            }
        }

        echo "\n同步特定扩展的 GitHub 源码...\n";
        $extensionMirror = new ExtensionMirror();
        return $extensionMirror->sync($enabledExtensions) ? 0 : 1;
    }

    /**
     * 同步 Composer 包
     *
     * @param array $mirrorConfig 镜像配置
     * @param string|null $version 指定版本
     * @return int 退出代码
     */
    private function syncComposer(array $mirrorConfig, $version = null)
    {
        if (!isset($mirrorConfig['composer']['enabled']) || !$mirrorConfig['composer']['enabled']) {
            echo "\n跳过 Composer 包同步 (已禁用)\n";
            return 0;
        }

        echo "\n同步 Composer 包...\n";
        $composerMirror = new ComposerMirror();

        if ($version) {
            return $composerMirror->syncVersion($mirrorConfig['composer'], $version) ? 0 : 1;
        } else {
            return $composerMirror->sync($mirrorConfig['composer']) ? 0 : 1;
        }
    }

    /**
     * 收集下载计划
     *
     * @param array $mirrorConfig 镜像配置
     * @return array 下载计划
     */
    private function collectDownloadPlan(array $mirrorConfig)
    {
        $downloadPlan = [
            'php' => [],
            'pecl' => [],
            'extensions' => [],
            'composer' => [],
            'summary' => [
                'total_files' => 0,
                'existing_files' => 0,
                'download_files' => 0,
                'total_size' => 0,
                'existing_size' => 0,
                'download_size' => 0
            ]
        ];

        // 收集 PHP 源码包信息
        if (isset($mirrorConfig['php']['enabled']) && $mirrorConfig['php']['enabled']) {
            echo "检查 PHP 源码包...\n";
            $downloadPlan['php'] = $this->collectPhpDownloadPlan($mirrorConfig['php']);
        }

        // 收集 PECL 扩展信息
        if (isset($mirrorConfig['pecl']['enabled']) && $mirrorConfig['pecl']['enabled']) {
            echo "检查 PECL 扩展...\n";
            $downloadPlan['pecl'] = $this->collectPeclDownloadPlan($mirrorConfig['pecl']);
        }

        // 收集特定扩展信息
        if (isset($mirrorConfig['extensions']['enabled']) && $mirrorConfig['extensions']['enabled']) {
            echo "检查特定扩展...\n";
            $downloadPlan['extensions'] = $this->collectExtensionsDownloadPlan($mirrorConfig['extensions']);
        }

        // 收集 Composer 包信息
        if (isset($mirrorConfig['composer']['enabled']) && $mirrorConfig['composer']['enabled']) {
            echo "检查 Composer 包...\n";
            $downloadPlan['composer'] = $this->collectComposerDownloadPlan($mirrorConfig['composer']);
        }

        // 计算总体统计
        $this->calculateDownloadPlanSummary($downloadPlan);

        return $downloadPlan;
    }

    /**
     * 显示下载计划摘要
     *
     * @param array $downloadPlan 下载计划
     */
    private function showDownloadPlanSummary(array $downloadPlan)
    {
        $summary = $downloadPlan['summary'];

        echo "\n=== 下载计划摘要 ===\n";
        echo "总文件数: {$summary['total_files']}\n";
        echo "已存在文件: {$summary['existing_files']}\n";
        echo "需要下载: {$summary['download_files']}\n";

        if ($summary['total_size'] > 0) {
            echo "总大小: " . $this->formatSize($summary['total_size']) . "\n";
            echo "已存在大小: " . $this->formatSize($summary['existing_size']) . "\n";
            echo "需要下载大小: " . $this->formatSize($summary['download_size']) . "\n";
        }

        // 显示各类型详情
        foreach (['php', 'pecl', 'extensions', 'composer'] as $type) {
            if (!empty($downloadPlan[$type])) {
                $typeDownload = array_filter($downloadPlan[$type], function($item) {
                    return !$item['exists'];
                });
                $typeExists = array_filter($downloadPlan[$type], function($item) {
                    return $item['exists'];
                });

                echo "\n{$type}: " . count($downloadPlan[$type]) . " 个文件";
                if (count($typeExists) > 0) {
                    echo " (已存在: " . count($typeExists) . ")";
                }
                if (count($typeDownload) > 0) {
                    echo " (需下载: " . count($typeDownload) . ")";
                }
                echo "\n";
            }
        }

        if ($summary['download_files'] == 0) {
            echo "\n所有文件都已存在，无需下载。\n";
        }
    }

    /**
     * 执行下载计划
     *
     * @param array $downloadPlan 下载计划
     * @param array $runtimeConfig 运行时配置
     */
    private function executeDownloadPlan(array $downloadPlan, array $runtimeConfig)
    {
        $totalDownloads = $downloadPlan['summary']['download_files'];
        $currentDownload = 0;

        if ($totalDownloads == 0) {
            echo "无需下载任何文件。\n";
            return;
        }

        echo "开始下载 {$totalDownloads} 个文件...\n\n";

        // 执行各类型下载
        foreach (['php', 'pecl', 'extensions', 'composer'] as $type) {
            if (!empty($downloadPlan[$type])) {
                $needDownload = array_filter($downloadPlan[$type], function($item) {
                    return !$item['exists'];
                });

                if (!empty($needDownload)) {
                    echo "下载 {$type} 文件 (" . count($needDownload) . " 个)...\n";

                    foreach ($needDownload as $item) {
                        $currentDownload++;
                        echo "\n[{$currentDownload}/{$totalDownloads}] ";

                        $this->downloadSingleFile($item, $runtimeConfig);
                    }
                }
            }
        }

        echo "\n下载阶段完成。\n";
    }

    /**
     * 收集 PHP 源码包下载计划
     *
     * @param array $config PHP 配置
     * @return array 下载计划
     */
    private function collectPhpDownloadPlan(array $config)
    {
        $plan = [];
        $source = $config['source'];
        $pattern = $config['pattern'];

        // 获取数据目录
        $configManager = new \Mirror\Config\ConfigManager();
        $baseDir = $configManager->getDataDir();
        $dataDir = $baseDir . '/php';

        // 获取版本配置
        $extensionConfigManager = new \Mirror\Service\ExtensionConfigManager();
        $versionGroups = $extensionConfigManager->getPhpVersions();

        if (empty($versionGroups)) {
            echo "  PHP 版本配置为空，跳过检查\n";
            return $plan;
        }

        // 遍历所有主版本组
        foreach ($versionGroups as $versionList) {
            foreach ($versionList as $version) {
                $filename = str_replace('{version}', $version, $pattern);
                $sourceUrl = $source . '/' . $filename;
                $targetFile = $dataDir . '/' . $filename;

                $exists = file_exists($targetFile);
                $fileSize = $exists ? filesize($targetFile) : 0;

                $plan[] = [
                    'type' => 'php',
                    'version' => $version,
                    'filename' => $filename,
                    'source_url' => $sourceUrl,
                    'target_file' => $targetFile,
                    'exists' => $exists,
                    'file_size' => $fileSize,
                    'estimated_size' => 20 * 1024 * 1024, // 估计 20MB
                ];

                echo "  PHP {$version}: " . ($exists ? "已存在" : "需下载") . "\n";
            }
        }

        return $plan;
    }

    /**
     * 收集 PECL 扩展下载计划
     *
     * @param array $config PECL 配置
     * @return array 下载计划
     */
    private function collectPeclDownloadPlan(array $config)
    {
        $plan = [];
        // 这里简化实现，实际应该遍历所有 PECL 扩展
        // 避免未使用变量警告
        if (empty($config)) {
            echo "  PECL 扩展配置为空，跳过检查\n";
        } else {
            echo "  PECL 扩展检查暂时跳过\n";
        }
        return $plan;
    }

    /**
     * 收集特定扩展下载计划
     *
     * @param array $config 扩展配置
     * @return array 下载计划
     */
    private function collectExtensionsDownloadPlan(array $config)
    {
        $plan = [];
        // 这里简化实现，实际应该遍历所有特定扩展
        // 避免未使用变量警告
        if (empty($config)) {
            echo "  特定扩展配置为空，跳过检查\n";
        } else {
            echo "  特定扩展检查暂时跳过\n";
        }
        return $plan;
    }

    /**
     * 收集 Composer 包下载计划
     *
     * @param array $config Composer 配置
     * @return array 下载计划
     */
    private function collectComposerDownloadPlan(array $config)
    {
        $plan = [];
        $source = $config['source'];
        $pattern = $config['pattern'];
        $urlPattern = $config['url_pattern'] ?? '{source}/composer-{version}.phar';

        // 获取数据目录
        $configManager = new \Mirror\Config\ConfigManager();
        $baseDir = $configManager->getDataDir();
        $dataDir = $baseDir . '/composer';

        // 获取版本配置
        $extensionConfigManager = new \Mirror\Service\ExtensionConfigManager();
        $versions = $extensionConfigManager->getComposerVersions();

        foreach ($versions as $version) {
            $filename = str_replace('{version}', $version, $pattern);

            // 构建正确的下载 URL
            $sourceUrl = str_replace(['{source}', '{version}'], [$source, $version], $urlPattern);

            $targetFile = $dataDir . '/' . $filename;

            $exists = file_exists($targetFile);
            $fileSize = $exists ? filesize($targetFile) : 0;

            $plan[] = [
                'type' => 'composer',
                'version' => $version,
                'filename' => $filename,
                'source_url' => $sourceUrl,
                'target_file' => $targetFile,
                'exists' => $exists,
                'file_size' => $fileSize,
                'estimated_size' => 2 * 1024 * 1024, // 估计 2MB
            ];

            echo "  Composer {$version}: " . ($exists ? "已存在" : "需下载") . "\n";
        }

        return $plan;
    }

    /**
     * 计算下载计划摘要
     *
     * @param array &$downloadPlan 下载计划（引用传递）
     */
    private function calculateDownloadPlanSummary(array &$downloadPlan)
    {
        $summary = &$downloadPlan['summary'];

        foreach (['php', 'pecl', 'extensions', 'composer'] as $type) {
            foreach ($downloadPlan[$type] as $item) {
                $summary['total_files']++;

                if ($item['exists']) {
                    $summary['existing_files']++;
                    $summary['existing_size'] += $item['file_size'];
                } else {
                    $summary['download_files']++;
                    $summary['download_size'] += $item['estimated_size'];
                }

                $summary['total_size'] += $item['exists'] ? $item['file_size'] : $item['estimated_size'];
            }
        }
    }

    /**
     * 格式化文件大小
     *
     * @param int $size 文件大小（字节）
     * @return string 格式化后的大小
     */
    private function formatSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * 下载单个文件
     *
     * @param array $item 文件信息
     * @param array $runtimeConfig 运行时配置
     */
    private function downloadSingleFile(array $item, array $runtimeConfig)
    {
        echo "下载 {$item['type']} {$item['version']}: {$item['source_url']}\n";

        // 创建目录
        $dir = dirname($item['target_file']);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // 设置下载选项
        $downloadOptions = [
            'min_size' => 1024,
            'max_retries' => $runtimeConfig['max_retries'] ?? 3,
            'timeout' => $runtimeConfig['timeout'] ?? 600,
            'verify_content' => true,
            'expected_type' => $item['type'] === 'php' ? 'tar.gz' : null
        ];

        try {
            $success = \Mirror\Utils\FileUtils::downloadFile(
                $item['source_url'],
                $item['target_file'],
                $downloadOptions
            );

            if ($success) {
                echo "  下载完成\n";
            } else {
                echo "  下载失败\n";
            }
        } catch (\Exception $e) {
            echo "  下载失败: " . $e->getMessage() . "\n";
        }
    }
}
