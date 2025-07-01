<?php

namespace Mirror\Mirror;

use Mirror\Service\ExtensionConfigManager;

/**
 * PHP镜像类
 */
class PhpMirror
{
    /**
     * 同步PHP源码包
     *
     * @param array $config 配置
     * @return bool 是否成功
     */
    public function sync(array $config)
    {
        echo "同步 PHP 源码包...\n";

        $source = $config['source'];
        $pattern = $config['pattern'];

        // 获取数据目录
        $configManager = new \Mirror\Config\ConfigManager();
        $baseDir = $configManager->getDataDir();
        $dataDir = $baseDir . '/php';  // 强制添加php子目录

        // 确保目录存在
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        // 获取版本配置
        $extensionConfigManager = new ExtensionConfigManager();
        $versions = $extensionConfigManager->getPhpVersions();

        if (empty($versions)) {
            echo "  错误: 无法获取PHP版本配置\n";
            return false;
        }

        // === 预检查阶段 ===
        echo "\n=== 预检查阶段 ===\n";
        $allVersions = [];
        foreach ($versions as $versionList) {
            $allVersions = array_merge($allVersions, $versionList);
        }

        $downloadPlan = $this->collectVersionDownloadPlan($source, $pattern, $dataDir, $allVersions);

        // 显示下载计划
        $this->showAllVersionsDownloadPlan($downloadPlan);

        // === 下载阶段 ===
        echo "\n=== 下载阶段 ===\n";
        return $this->executeVersionDownloadPlan($downloadPlan);
    }

    /**
     * 同步指定版本的PHP源码包
     *
     * @param array $config 配置
     * @param string $majorVersion 指定主版本 (如 8.3)
     * @return bool 是否成功
     */
    public function syncVersion(array $config, $majorVersion)
    {
        echo "同步 PHP 指定版本: $majorVersion\n";

        $source = $config['source'];
        $pattern = $config['pattern'];

        // 获取数据目录
        $configManager = new \Mirror\Config\ConfigManager();
        $baseDir = $configManager->getDataDir();
        $dataDir = $baseDir . '/php';  // 强制添加php子目录

        // 确保目录存在
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        // 获取版本配置
        $extensionConfigManager = new \Mirror\Service\ExtensionConfigManager();
        $versionGroups = $extensionConfigManager->getPhpVersions();

        if (empty($versionGroups)) {
            echo "  错误: PHP 版本配置为空\n";
            return false;
        }

        // 检查版本是否在配置中
        if (!isset($versionGroups[$majorVersion])) {
            echo "  错误: 版本 $majorVersion 不在配置的版本列表中\n";
            echo "  可用版本: " . implode(', ', array_keys($versionGroups)) . "\n";
            return false;
        }

        // 获取版本列表
        $versions = $versionGroups[$majorVersion];

        // === 预检查阶段 ===
        echo "\n=== 预检查阶段 ===\n";
        $downloadPlan = $this->collectVersionDownloadPlan($source, $pattern, $dataDir, $versions);

        // 显示下载计划
        $this->showVersionDownloadPlan($downloadPlan, $majorVersion);

        // === 下载阶段 ===
        echo "\n=== 下载阶段 ===\n";
        return $this->executeVersionDownloadPlan($downloadPlan);
    }

    /**
     * 显示所有版本下载计划
     *
     * @param array $downloadPlan 下载计划
     */
    private function showAllVersionsDownloadPlan($downloadPlan)
    {
        $totalFiles = count($downloadPlan);
        $existingFiles = 0;
        $validFiles = 0;
        $needDownload = 0;
        $totalSize = 0;
        $existingSize = 0;
        $downloadSize = 0;

        // 按主版本分组统计
        $versionGroups = [];

        foreach ($downloadPlan as $item) {
            // 提取主版本号
            if (preg_match('/^(\d+\.\d+)\./', $item['version'], $matches)) {
                $majorVersion = $matches[1];
                if (!isset($versionGroups[$majorVersion])) {
                    $versionGroups[$majorVersion] = [
                        'total' => 0,
                        'existing' => 0,
                        'valid' => 0,
                        'need_download' => 0
                    ];
                }
                $versionGroups[$majorVersion]['total']++;
            }

            if ($item['exists']) {
                $existingFiles++;
                $existingSize += $item['file_size'];

                if (isset($majorVersion)) {
                    $versionGroups[$majorVersion]['existing']++;
                }

                if ($item['is_valid']) {
                    $validFiles++;
                    if (isset($majorVersion)) {
                        $versionGroups[$majorVersion]['valid']++;
                    }
                } else {
                    $needDownload++;
                    $downloadSize += $item['estimated_size'];
                    if (isset($majorVersion)) {
                        $versionGroups[$majorVersion]['need_download']++;
                    }
                }
            } else {
                $needDownload++;
                $downloadSize += $item['estimated_size'];
                if (isset($majorVersion)) {
                    $versionGroups[$majorVersion]['need_download']++;
                }
            }

            $totalSize += $item['exists'] ? $item['file_size'] : $item['estimated_size'];
        }

        echo "\n=== PHP 全版本下载计划摘要 ===\n";
        echo "总文件数: {$totalFiles}\n";
        echo "已存在文件: {$existingFiles} (有效: {$validFiles}, 无效: " . ($existingFiles - $validFiles) . ")\n";
        echo "需要下载: {$needDownload}\n";

        if ($totalSize > 0) {
            echo "总大小: " . $this->formatSize($totalSize) . "\n";
            echo "已存在大小: " . $this->formatSize($existingSize) . "\n";
            echo "需要下载大小: " . $this->formatSize($downloadSize) . "\n";
        }

        // 显示各主版本统计
        echo "\n按主版本统计:\n";
        ksort($versionGroups);
        foreach ($versionGroups as $major => $stats) {
            echo "  PHP {$major}: {$stats['total']} 个文件";
            if ($stats['existing'] > 0) {
                echo " (已存在: {$stats['existing']}";
                if ($stats['valid'] > 0) {
                    echo ", 有效: {$stats['valid']}";
                }
                echo ")";
            }
            if ($stats['need_download'] > 0) {
                echo " (需下载: {$stats['need_download']})";
            }
            echo "\n";
        }

        if ($needDownload == 0) {
            echo "\n所有文件都已存在且有效，无需下载。\n";
        }
    }

    /**
     * 验证 PHP 源码包
     *
     * @param string $filePath 文件路径
     * @param string $version 版本号
     * @return bool 是否验证通过
     */
    private function validatePhpSourcePackage($filePath, $version)
    {
        // 检查是否为有效的 tar.gz 文件
        if (!$this->isValidTarGz($filePath)) {
            return false;
        }

        // 尝试列出压缩包内容
        try {
            $output = [];
            $returnCode = 0;
            exec("tar -tzf " . escapeshellarg($filePath) . " 2>/dev/null | head -20", $output, $returnCode);

            if ($returnCode !== 0) {
                echo "  验证失败: 无法读取 tar.gz 文件内容\n";
                return false;
            }

            // 检查是否包含 PHP 源码的关键文件
            $hasConfigureScript = false;
            $hasMainDirectory = false;
            $expectedDir = "php-$version/";

            foreach ($output as $line) {
                if (strpos($line, $expectedDir) === 0) {
                    $hasMainDirectory = true;
                }
                if (strpos($line, 'configure') !== false) {
                    $hasConfigureScript = true;
                }
            }

            if (!$hasMainDirectory) {
                echo "  验证失败: 压缩包不包含预期的目录结构\n";
                return false;
            }

            if (!$hasConfigureScript) {
                echo "  验证失败: 压缩包不包含 configure 脚本\n";
                return false;
            }

            return true;
        } catch (\Exception $e) {
            echo "  验证失败: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * 检查是否为有效的 tar.gz 文件
     *
     * @param string $filePath 文件路径
     * @return bool 是否有效
     */
    private function isValidTarGz($filePath)
    {
        // 检查文件头
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 3);
        fclose($handle);

        // Gzip 文件的魔数是 1f 8b 08
        return substr($header, 0, 2) === "\x1f\x8b";
    }

    /**
     * 验证已存在的文件
     *
     * @param string $filePath 文件路径
     * @param string $version 版本号
     * @return bool 是否验证通过
     */
    private function validateExistingFile($filePath, $version)
    {
        // 检查文件大小
        $fileSize = filesize($filePath);
        if ($fileSize < 1024 * 1024 * 5) { // 小于 5MB
            return false;
        }

        // 检查文件格式
        return $this->validatePhpSourcePackage($filePath, $version);
    }


    /**
     * 收集版本下载计划
     *
     * @param string $source 源地址
     * @param string $pattern 文件名模式
     * @param string $dataDir 数据目录
     * @param array $versions 版本列表
     * @return array 下载计划
     */
    private function collectVersionDownloadPlan($source, $pattern, $dataDir, $versions)
    {
        $plan = [];

        echo "检查 PHP 版本文件...\n";

        foreach ($versions as $version) {
            $filename = str_replace('{version}', $version, $pattern);
            $sourceUrl = $source . '/' . $filename;
            $targetFile = $dataDir . '/' . $filename;

            $exists = file_exists($targetFile);
            $fileSize = $exists ? filesize($targetFile) : 0;
            $isValid = $exists ? $this->validateExistingFile($targetFile, $version) : false;

            // 生成多个下载URL，包括museum源
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
                'estimated_size' => 20 * 1024 * 1024, // 估计 20MB
                'needs_download' => !$exists || !$isValid
            ];

            if ($exists) {
                if ($isValid) {
                    echo "  PHP {$version}: 已存在且有效\n";
                } else {
                    echo "  PHP {$version}: 已存在但无效，需重新下载\n";
                }
            } else {
                echo "  PHP {$version}: 需下载\n";
            }
        }

        return $plan;
    }

    /**
     * 显示版本下载计划
     *
     * @param array $downloadPlan 下载计划
     * @param string $majorVersion 主版本号
     */
    private function showVersionDownloadPlan($downloadPlan, $majorVersion)
    {
        $totalFiles = count($downloadPlan);
        $existingFiles = 0;
        $validFiles = 0;
        $needDownload = 0;
        $totalSize = 0;
        $existingSize = 0;
        $downloadSize = 0;

        foreach ($downloadPlan as $item) {
            if ($item['exists']) {
                $existingFiles++;
                $existingSize += $item['file_size'];

                if ($item['is_valid']) {
                    $validFiles++;
                } else {
                    $needDownload++;
                    $downloadSize += $item['estimated_size'];
                }
            } else {
                $needDownload++;
                $downloadSize += $item['estimated_size'];
            }

            $totalSize += $item['exists'] ? $item['file_size'] : $item['estimated_size'];
        }

        echo "\n=== PHP {$majorVersion} 下载计划摘要 ===\n";
        echo "总文件数: {$totalFiles}\n";
        echo "已存在文件: {$existingFiles} (有效: {$validFiles}, 无效: " . ($existingFiles - $validFiles) . ")\n";
        echo "需要下载: {$needDownload}\n";

        if ($totalSize > 0) {
            echo "总大小: " . $this->formatSize($totalSize) . "\n";
            echo "已存在大小: " . $this->formatSize($existingSize) . "\n";
            echo "需要下载大小: " . $this->formatSize($downloadSize) . "\n";
        }

        if ($needDownload == 0) {
            echo "\n所有文件都已存在且有效，无需下载。\n";
        } else {
            echo "\n将要下载的版本:\n";
            foreach ($downloadPlan as $item) {
                if ($item['needs_download']) {
                    $reason = $item['exists'] ? '(文件无效)' : '(文件不存在)';
                    echo "  - PHP {$item['version']} {$reason}\n";
                }
            }
        }
    }

    /**
     * 执行版本下载计划
     *
     * @param array $downloadPlan 下载计划
     * @return bool 是否成功
     */
    private function executeVersionDownloadPlan($downloadPlan)
    {
        $needDownload = array_filter($downloadPlan, function($item) {
            return $item['needs_download'];
        });

        if (empty($needDownload)) {
            echo "无需下载任何文件。\n";
            return true;
        }

        $totalDownloads = count($needDownload);
        $currentDownload = 0;
        $success = true;

        echo "开始下载 {$totalDownloads} 个文件...\n\n";

        foreach ($needDownload as $item) {
            $currentDownload++;
            echo "[{$currentDownload}/{$totalDownloads}] ";

            if (!$this->downloadSingleVersion($item)) {
                $success = false;
            }

            echo "\n";
        }

        echo "下载阶段完成。\n";
        return $success;
    }

    /**
     * 下载单个版本
     *
     * @param array $item 版本信息
     * @return bool 是否成功
     */
    private function downloadSingleVersion($item)
    {
        echo "下载 PHP {$item['version']}\n";

        // 如果文件存在但无效，先删除
        if ($item['exists'] && !$item['is_valid']) {
            echo "  删除无效文件...\n";
            unlink($item['target_file']);
        }

        // 设置下载选项
        $downloadOptions = [
            'min_size' => 1024 * 1024 * 5,  // PHP 源码包至少 5MB
            'max_retries' => 1,             // 每个URL只重试1次，因为我们有多个URL
            'timeout' => 600,               // PHP 源码包较大，增加超时时间
            'verify_content' => true,
            'expected_type' => 'tar.gz'
        ];

        // 获取下载URL列表
        $downloadUrls = isset($item['download_urls']) ? $item['download_urls'] : [$item['source_url']];

        foreach ($downloadUrls as $index => $url) {
            $urlNumber = $index + 1;
            $totalUrls = count($downloadUrls);
            echo "  尝试源 {$urlNumber}/{$totalUrls}: {$url}\n";

            try {
                $success = \Mirror\Utils\FileUtils::downloadFile(
                    $url,
                    $item['target_file'],
                    $downloadOptions
                );

                if ($success) {
                    // 额外验证 PHP 源码包
                    if ($this->validatePhpSourcePackage($item['target_file'], $item['version'])) {
                        echo "  PHP {$item['version']} 下载并验证完成\n";
                        return true;
                    } else {
                        echo "  源码包验证失败，尝试下一个源...\n";
                        if (file_exists($item['target_file'])) {
                            unlink($item['target_file']);
                        }
                    }
                } else {
                    echo "  下载失败，尝试下一个源...\n";
                }
            } catch (\Exception $e) {
                echo "  下载异常: " . $e->getMessage() . "，尝试下一个源...\n";
            }
        }

        echo "  错误: PHP {$item['version']} 所有源都下载失败\n";
        return false;
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
     * 获取PHP版本的多个下载URL
     *
     * @param string $version PHP版本
     * @param string $primaryUrl 主要URL
     * @return array 下载URL列表
     */
    private function getPhpDownloadUrls($version, $primaryUrl)
    {
        $urls = [$primaryUrl];

        // 为早期PHP版本添加museum源
        if ($this->isEarlyPhpVersion($version)) {
            $museumUrl = "https://museum.php.net/php5/php-{$version}.tar.gz";
            $urls[] = $museumUrl;
        }

        return $urls;
    }

    /**
     * 检查是否为早期PHP版本（需要使用museum源）
     *
     * @param string $version PHP版本
     * @return bool
     */
    private function isEarlyPhpVersion($version)
    {
        // 解析版本号
        if (!preg_match('/^(\d+)\.(\d+)\.(\d+)/', $version, $matches)) {
            return false;
        }

        $major = (int)$matches[1];
        $minor = (int)$matches[2];
        $patch = (int)$matches[3];

        // PHP 5.4.0 - 5.4.44 的早期版本在官方源不可用
        if ($major == 5 && $minor == 4) {
            // 5.4.45是最后一个版本，在官方源可用
            return $patch < 45;
        }

        // PHP 5.3及更早版本
        if ($major < 5 || ($major == 5 && $minor < 4)) {
            return true;
        }

        // PHP 5.5.0 - 5.5.9 的早期版本也可能需要museum源
        if ($major == 5 && $minor == 5) {
            return $patch < 10;
        }

        return false;
    }

    /**
     * 清理PHP源码包
     *
     * @param array $config 配置
     * @return bool 是否成功
     */
    public function clean(array $config)
    {
        echo "清理 PHP 源码包...\n";

        // 避免未使用变量警告
        if (empty($config)) {
            echo "  配置为空，跳过清理\n";
        } else {
            echo "  清理功能待实现\n";
        }

        return true;
    }
}
