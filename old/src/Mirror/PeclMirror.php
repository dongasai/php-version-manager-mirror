<?php

namespace Mirror\Mirror;

use Mirror\Utils\FileUtils;
use Mirror\Service\ExtensionConfigManager;

/**
 * PECL镜像类
 */
class PeclMirror
{
    /**
     * 同步PECL扩展包
     *
     * @param array $config 配置
     * @return bool 是否成功
     */
    public function sync(array $config)
    {
        echo "同步 PECL 扩展包...\n";

        $source = $config['source'];
        $pattern = $config['pattern'];

        // 获取数据目录
        $configManager = new \Mirror\Config\ConfigManager();
        $baseDir = $configManager->getDataDir();
        $dataDir = $baseDir . '/pecl';  // 强制添加pecl子目录

        // 确保目录存在
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        // 获取扩展版本配置
        $extensionConfigManager = new ExtensionConfigManager();
        $extensions = $extensionConfigManager->getAllPeclExtensionVersions();

        if (empty($extensions)) {
            echo "  错误: 无法获取PECL扩展版本配置\n";
            return false;
        }

        $success = true;

        // 遍历扩展
        foreach ($extensions as $extension => $versions) {
            if (!$this->syncExtensionVersions($source, $pattern, $dataDir, $extension, $versions)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * 同步指定PECL扩展
     *
     * @param array $config 配置
     * @param string $extensionName 指定扩展名
     * @return bool 是否成功
     */
    public function syncExtension(array $config, $extensionName)
    {
        echo "同步 PECL 指定扩展: $extensionName\n";

        $source = $config['source'];
        $pattern = $config['pattern'];

        // 获取数据目录
        $configManager = new \Mirror\Config\ConfigManager();
        $baseDir = $configManager->getDataDir();
        $dataDir = $baseDir . '/pecl';  // 强制添加pecl子目录

        // 确保目录存在
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        // 获取扩展版本配置
        $extensionConfigManager = new ExtensionConfigManager();
        $versions = $extensionConfigManager->getPeclExtensionVersions($extensionName);

        if (empty($versions)) {
            echo "  错误: 扩展 $extensionName 不在配置中或版本为空\n";
            return false;
        }

        return $this->syncExtensionVersions($source, $pattern, $dataDir, $extensionName, $versions);
    }

    /**
     * 同步扩展的所有版本
     *
     * @param string $source 源地址
     * @param string $pattern 文件名模式
     * @param string $dataDir 数据目录
     * @param string $extension 扩展名
     * @param array $versions 版本列表
     * @return bool 是否成功
     */
    private function syncExtensionVersions($source, $pattern, $dataDir, $extension, $versions)
    {
        $success = true;
        foreach ($versions as $version) {
            if (!$this->downloadExtensionVersion($source, $pattern, $dataDir, $extension, $version)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * 下载指定扩展版本
     *
     * @param string $source 源地址
     * @param string $pattern 文件名模式
     * @param string $dataDir 数据目录
     * @param string $extension 扩展名
     * @param string $version 版本号
     * @return bool 是否成功
     */
    private function downloadExtensionVersion($source, $pattern, $dataDir, $extension, $version)
    {
        $filename = str_replace(['{extension}', '{version}'], [$extension, $version], $pattern);
        $sourceUrl = $source . '/' . $filename;
        $targetFile = $dataDir . '/' . $filename;

        // 如果文件不存在，则下载
        if (!file_exists($targetFile)) {
            echo "  下载 $extension $version: $sourceUrl\n";

            // 设置下载选项
            $downloadOptions = [
                'min_size' => 1024 * 10,       // PECL 扩展至少 10KB
                'max_retries' => 3,
                'timeout' => 300,
                'verify_content' => true,
                'expected_type' => 'tgz'
            ];

            try {
                $success = FileUtils::downloadFile($sourceUrl, $targetFile, $downloadOptions);
                if ($success) {
                    // 额外验证 PECL 扩展包
                    if ($this->validatePeclPackage($targetFile, $extension, $version)) {
                        echo "  $extension $version 下载并验证完成\n";
                        return true;
                    } else {
                        echo "  错误: $extension $version 扩展包验证失败\n";
                        if (file_exists($targetFile)) {
                            unlink($targetFile);
                        }
                        return false;
                    }
                } else {
                    echo "  错误: $extension $version 下载失败\n";
                    return false;
                }
            } catch (\Exception $e) {
                echo "  错误: $extension $version 下载失败: " . $e->getMessage() . "\n";
                return false;
            }
        } else {
            // 验证已存在的文件
            if ($this->validateExistingFile($targetFile, $extension, $version)) {
                echo "  $extension $version 已存在且验证通过\n";
                return true;
            } else {
                echo "  $extension $version 文件损坏，重新下载\n";
                unlink($targetFile);
                return $this->downloadExtensionVersion($source, $pattern, $dataDir, $extension, $version);
            }
        }
    }

    /**
     * 验证 PECL 扩展包
     *
     * @param string $filePath 文件路径
     * @param string $extension 扩展名
     * @param string $version 版本号
     * @return bool 是否验证通过
     */
    private function validatePeclPackage($filePath, $extension, $version)
    {
        // 检查是否为有效的 tgz 文件
        if (!$this->isValidTgz($filePath)) {
            return false;
        }

        // 尝试列出压缩包内容
        try {
            $output = [];
            $returnCode = 0;
            exec("tar -tzf " . escapeshellarg($filePath) . " 2>/dev/null | head -20", $output, $returnCode);

            if ($returnCode !== 0) {
                echo "  验证失败: 无法读取 tgz 文件内容\n";
                return false;
            }

            // 检查是否包含扩展的关键文件
            $hasConfigFile = false;
            $hasSourceFiles = false;
            $expectedDir = "$extension-$version/";

            foreach ($output as $line) {
                if (strpos($line, $expectedDir) === 0) {
                    if (strpos($line, 'config.m4') !== false || strpos($line, 'config.w32') !== false) {
                        $hasConfigFile = true;
                    }
                    if (strpos($line, '.c') !== false || strpos($line, '.h') !== false) {
                        $hasSourceFiles = true;
                    }
                }
            }

            if (!$hasConfigFile) {
                echo "  验证失败: 扩展包不包含配置文件 (config.m4 或 config.w32)\n";
                return false;
            }

            if (!$hasSourceFiles) {
                echo "  验证失败: 扩展包不包含源代码文件\n";
                return false;
            }

            return true;
        } catch (\Exception $e) {
            echo "  验证失败: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * 检查是否为有效的 tgz 文件
     *
     * @param string $filePath 文件路径
     * @return bool 是否有效
     */
    private function isValidTgz($filePath)
    {
        // 检查文件头
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 3);
        fclose($handle);

        // Gzip 文件的魔数是 1f 8b
        return substr($header, 0, 2) === "\x1f\x8b";
    }

    /**
     * 验证已存在的文件
     *
     * @param string $filePath 文件路径
     * @param string $extension 扩展名
     * @param string $version 版本号
     * @return bool 是否验证通过
     */
    private function validateExistingFile($filePath, $extension, $version)
    {
        // 检查文件大小
        $fileSize = filesize($filePath);
        if ($fileSize < 1024 * 10) { // 小于 10KB
            return false;
        }

        // 检查文件格式
        return $this->validatePeclPackage($filePath, $extension, $version);
    }

    /**
     * 清理PECL扩展包
     *
     * @param array $config 配置
     * @return bool 是否成功
     */
    public function clean(array $config)
    {
        echo "清理 PECL 扩展包...\n";

        // 实现清理逻辑
        // ...

        return true;
    }
}
