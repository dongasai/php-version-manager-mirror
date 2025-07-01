<?php

namespace Mirror\Mirror;

use Mirror\Utils\FileUtils;
use Mirror\Service\ExtensionConfigManager;

/**
 * 扩展镜像类
 */
class ExtensionMirror
{
    /**
     * 同步特定扩展的GitHub源码
     *
     * @param array $config 配置
     * @return bool 是否成功
     */
    public function sync(array $config)
    {
        echo "同步特定扩展的 GitHub 源码...\n";

        // 获取数据目录
        $configManager = new \Mirror\Config\ConfigManager();
        $baseDir = $configManager->getDataDir();

        // 获取扩展版本配置
        $extensionConfigManager = new ExtensionConfigManager();
        $extensions = $extensionConfigManager->getAllGithubExtensionVersions();

        if (empty($extensions)) {
            echo "  错误: 无法获取GitHub扩展版本配置\n";
            return false;
        }

        $success = true;

        // 遍历扩展
        foreach ($extensions as $extension => $versions) {
            $extConfig = $extensionConfigManager->getGithubExtensionConfig($extension);
            if (!empty($extConfig)) {
                $extConfig['versions'] = $versions;
                if (!$this->syncExtension($baseDir, $extension, $extConfig)) {
                    $success = false;
                }
            } else {
                echo "  警告: 扩展 $extension 配置不完整，跳过\n";
            }
        }

        return $success;
    }

    /**
     * 同步单个扩展
     *
     * @param string $baseDir 基础数据目录
     * @param string $extension 扩展名
     * @param array $extConfig 扩展配置
     * @return bool 是否成功
     */
    private function syncExtension($baseDir, $extension, $extConfig)
    {
        $source = $extConfig['source'];
        $pattern = $extConfig['pattern'];

        // 根据URL转换规则，GitHub扩展使用 /github/{owner}/{repo}/ 目录结构
        $githubInfo = $this->parseGithubSource($source);
        if ($githubInfo) {
            $dataDir = $baseDir . '/github/' . $githubInfo['owner'] . '/' . $githubInfo['repo'];
        } else {
            // 兼容旧的目录结构
            $dataDir = $baseDir . '/extensions/' . $extension;
        }

        // 确保目录存在
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        $success = true;

        // 遍历版本
        foreach ($extConfig['versions'] as $version) {
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
        $filename = str_replace('{version}', $version, $pattern);
        $sourceUrl = $source . '/' . $filename;
        $targetFile = $dataDir . '/' . $filename;

        // 如果文件不存在，则下载
        if (!file_exists($targetFile)) {
            echo "  下载 $extension $version: $sourceUrl\n";

            // 设置下载选项
            $downloadOptions = [
                'min_size' => 1024 * 50,       // GitHub 扩展源码至少 50KB
                'max_retries' => 3,
                'timeout' => 300,
                'verify_content' => true,
                'expected_type' => 'tar.gz'
            ];

            try {
                $success = FileUtils::downloadFile($sourceUrl, $targetFile, $downloadOptions);
                if ($success) {
                    // 额外验证 GitHub 扩展包
                    if ($this->validateGithubExtensionPackage($targetFile, $extension, $version)) {
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
     * 解析GitHub源地址，提取owner和repo信息
     *
     * @param string $source GitHub源地址
     * @return array|null 包含owner和repo的数组，失败返回null
     */
    private function parseGithubSource($source)
    {
        // 匹配GitHub源地址格式：https://github.com/{owner}/{repo}/archive/refs/tags
        if (preg_match('#^https://github\.com/([^/]+)/([^/]+)/archive/refs/tags$#', $source, $matches)) {
            return [
                'owner' => $matches[1],
                'repo' => $matches[2]
            ];
        }

        return null;
    }

    /**
     * 验证 GitHub 扩展包
     *
     * @param string $filePath 文件路径
     * @param string $extension 扩展名
     * @param string $version 版本号
     * @return bool 是否验证通过
     */
    private function validateGithubExtensionPackage($filePath, $extension, $version)
    {
        // 检查是否为有效的 tar.gz 文件
        if (!$this->isValidTarGz($filePath)) {
            return false;
        }

        // 尝试列出压缩包内容
        try {
            $output = [];
            $returnCode = 0;
            exec("tar -tzf " . escapeshellarg($filePath) . " 2>/dev/null | head -30", $output, $returnCode);

            if ($returnCode !== 0) {
                echo "  验证失败: 无法读取 tar.gz 文件内容\n";
                return false;
            }

            // GitHub 下载的压缩包通常包含项目名-版本号的目录
            $hasMainDirectory = false;
            $hasSourceFiles = false;
            $hasConfigFiles = false;

            foreach ($output as $line) {
                // 检查是否有主目录
                if (preg_match('/^[^\/]+\/$/', $line)) {
                    $hasMainDirectory = true;
                }

                // 检查是否有源代码文件
                if (strpos($line, '.c') !== false || strpos($line, '.h') !== false || strpos($line, '.php') !== false) {
                    $hasSourceFiles = true;
                }

                // 检查是否有配置文件
                if (strpos($line, 'config.m4') !== false ||
                    strpos($line, 'config.w32') !== false ||
                    strpos($line, 'CMakeLists.txt') !== false ||
                    strpos($line, 'Makefile') !== false) {
                    $hasConfigFiles = true;
                }
            }

            if (!$hasMainDirectory) {
                echo "  验证失败: 压缩包不包含主目录结构\n";
                return false;
            }

            if (!$hasSourceFiles) {
                echo "  验证失败: 压缩包不包含源代码文件\n";
                return false;
            }

            // 对于某些扩展，配置文件可能不是必需的
            if (!$hasConfigFiles) {
                echo "  警告: 压缩包不包含明显的配置文件，但继续验证\n";
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
        if ($fileSize < 1024 * 50) { // 小于 50KB
            return false;
        }

        // 检查文件格式
        return $this->validateGithubExtensionPackage($filePath, $extension, $version);
    }

    /**
     * 清理特定扩展的GitHub源码
     *
     * @param array $config 配置
     * @return bool 是否成功
     */
    public function clean(array $config)
    {
        echo "清理特定扩展的 GitHub 源码...\n";

        // 实现清理逻辑
        // ...

        return true;
    }
}
