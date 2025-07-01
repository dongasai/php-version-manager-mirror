<?php

namespace Mirror\Mirror;

use Mirror\Utils\FileUtils;
use Mirror\Service\ExtensionConfigManager;

/**
 * Composer镜像类
 */
class ComposerMirror
{
    /**
     * 同步Composer包
     *
     * @param array $config 配置
     * @return bool 是否成功
     */
    public function sync(array $config)
    {
        echo "同步 Composer 包...\n";

        $source = $config['source'];
        $pattern = $config['pattern'];

        // 获取数据目录
        $configManager = new \Mirror\Config\ConfigManager();
        $baseDir = $configManager->getDataDir();
        $dataDir = $baseDir . '/composer';  // 强制添加composer子目录

        // 确保目录存在
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        // 获取版本配置
        $extensionConfigManager = new ExtensionConfigManager();
        $versions = $extensionConfigManager->getComposerVersions();

        if (empty($versions)) {
            echo "  错误: 无法获取Composer版本配置\n";
            return false;
        }

        $success = true;

        // 遍历版本
        foreach ($versions as $version) {
            if (!$this->downloadVersion($source, $pattern, $dataDir, $version)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * 同步指定版本的Composer包
     *
     * @param array $config 配置
     * @param string $version 指定版本
     * @return bool 是否成功
     */
    public function syncVersion(array $config, $version)
    {
        echo "同步 Composer 指定版本: $version\n";

        $source = $config['source'];
        $pattern = $config['pattern'];

        // 获取数据目录
        $configManager = new \Mirror\Config\ConfigManager();
        $baseDir = $configManager->getDataDir();
        $dataDir = $baseDir . '/composer';  // 强制添加composer子目录

        // 确保目录存在
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        // 检查版本是否在配置的版本列表中
        if (!in_array($version, $config['versions'])) {
            echo "  警告: 版本 $version 不在配置的版本列表中，但仍尝试下载\n";
        }

        return $this->downloadVersion($source, $pattern, $dataDir, $version);
    }

    /**
     * 下载指定版本
     *
     * @param string $source 源地址
     * @param string $pattern 文件名模式
     * @param string $dataDir 数据目录
     * @param string $version 版本号
     * @return bool 是否成功
     */
    private function downloadVersion($source, $pattern, $dataDir, $version)
    {
        // 根据URL转换规则处理不同版本的URL和文件名
        if ($version === 'stable') {
            // 稳定版：源URL为 /download/composer.phar，目标文件为 composer.phar
            $sourceUrl = $source . '/composer.phar';
            $targetFile = $dataDir . '/composer.phar';
        } else {
            // 指定版本：源URL为 /download/{version}/composer.phar，目标文件为 composer-{version}.phar
            $sourceUrl = $source . '/' . $version . '/composer.phar';
            $targetFile = $dataDir . '/composer-' . $version . '.phar';
        }

        // 如果文件不存在，则下载
        if (!file_exists($targetFile)) {
            echo "  下载 Composer $version: $sourceUrl\n";

            // 设置下载选项
            $downloadOptions = [
                'min_size' => 1024 * 100,     // Composer 至少 100KB
                'max_retries' => 3,
                'timeout' => 300,
                'verify_content' => true,
                'expected_type' => 'phar'
            ];

            try {
                $success = FileUtils::downloadFile($sourceUrl, $targetFile, $downloadOptions);
                if ($success) {
                    // 额外验证 Composer PHAR 文件
                    if ($this->validateComposerPhar($targetFile)) {
                        echo "  Composer $version 下载并验证完成\n";
                        return true;
                    } else {
                        echo "  错误: Composer $version PHAR 文件验证失败\n";
                        if (file_exists($targetFile)) {
                            unlink($targetFile);
                        }
                        return false;
                    }
                } else {
                    echo "  错误: Composer $version 下载失败\n";
                    return false;
                }
            } catch (\Exception $e) {
                echo "  错误: Composer $version 下载失败: " . $e->getMessage() . "\n";
                return false;
            }
        } else {
            // 验证已存在的文件
            if ($this->validateExistingFile($targetFile, $version)) {
                echo "  Composer $version 已存在且验证通过\n";
                return true;
            } else {
                echo "  Composer $version 文件损坏，重新下载\n";
                unlink($targetFile);
                return $this->downloadVersion($source, $pattern, $dataDir, $version);
            }
        }
    }

    /**
     * 验证 Composer PHAR 文件
     *
     * @param string $filePath 文件路径
     * @return bool 是否验证通过
     */
    private function validateComposerPhar($filePath)
    {
        // 检查文件是否可以作为 PHAR 执行
        try {
            // 尝试读取 PHAR 文件信息
            $phar = new \Phar($filePath);
            $metadata = $phar->getMetadata();

            // 检查是否包含 Composer 的关键文件
            if (!isset($phar['composer.json']) && !isset($phar['src/Composer/Composer.php'])) {
                echo "  验证失败: PHAR 文件不包含 Composer 核心文件\n";
                return false;
            }

            return true;
        } catch (\Exception $e) {
            echo "  验证失败: PHAR 文件格式错误: " . $e->getMessage() . "\n";
            return false;
        } catch (\Throwable $e) {
            echo "  验证失败: PHAR 文件严重错误: " . $e->getMessage() . "\n";
            return false;
        }
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
        if ($fileSize < 1024 * 100) { // 小于 100KB
            return false;
        }

        // 检查文件是否为 PHAR 格式
        return $this->validateComposerPhar($filePath);
    }

    /**
     * 清理Composer包
     *
     * @param array $config 配置
     * @return bool 是否成功
     */
    public function clean(array $config)
    {
        echo "清理 Composer 包...\n";

        // 实现清理逻辑
        // ...

        return true;
    }
}
