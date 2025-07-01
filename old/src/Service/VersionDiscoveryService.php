<?php

namespace Mirror\Service;

use Mirror\Service\ExtensionConfigManager;

/**
 * 版本发现服务
 *
 * 负责从各种源发现和获取版本信息
 */
class VersionDiscoveryService
{
    private $phpDiscovery;
    private $peclDiscovery;
    private $githubDiscovery;
    private $config;
    private $extensionConfigManager;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->phpDiscovery = new PhpVersionDiscovery();
        $this->peclDiscovery = new PeclVersionDiscovery();
        $this->githubDiscovery = new GithubVersionDiscovery();
        $this->extensionConfigManager = new ExtensionConfigManager();

        // 加载配置
        $configManager = new \Mirror\Config\ConfigManager();
        $this->config = $configManager->getMirrorConfig();
    }

    /**
     * 发现所有PHP版本
     *
     * @return array 版本信息数组
     */
    public function discoverPhpVersions()
    {
        echo "正在发现 PHP 版本...\n";

        $versions = $this->phpDiscovery->getAvailableVersions();

        if (empty($versions)) {
            echo "  警告: 无法从 API 获取 PHP 版本，使用配置文件中的版本\n";
            return $this->getConfigPhpVersions();
        }

        echo "  发现 " . count($versions) . " 个 PHP 版本\n";
        return $versions;
    }

    /**
     * 发现所有PECL扩展版本
     *
     * @param string|null $extensionName 指定扩展名，null表示所有扩展
     * @return array 扩展版本信息数组
     */
    public function discoverPeclVersions($extensionName = null)
    {
        echo "正在发现 PECL 扩展版本...\n";

        $extensions = [];
        $configExtensions = $this->config['pecl']['extensions'] ?? [];

        if ($extensionName) {
            if (in_array($extensionName, $configExtensions)) {
                $extensions[$extensionName] = $this->peclDiscovery->getExtensionVersions($extensionName);
            } else {
                echo "  警告: 扩展 $extensionName 不在配置中\n";
                return [];
            }
        } else {
            foreach ($configExtensions as $extension) {
                echo "  发现扩展: $extension\n";
                $versions = $this->peclDiscovery->getExtensionVersions($extension);
                if (!empty($versions)) {
                    $extensions[$extension] = $versions;
                    echo "    发现 " . count($versions) . " 个版本\n";
                } else {
                    echo "    警告: 无法获取扩展 $extension 的版本信息\n";
                }
            }
        }

        return $extensions;
    }

    /**
     * 发现所有GitHub扩展版本
     *
     * @param string|null $extensionName 指定扩展名，null表示所有扩展
     * @return array 扩展版本信息数组
     */
    public function discoverGithubVersions($extensionName = null)
    {
        echo "正在发现 GitHub 扩展版本...\n";

        $extensions = [];
        $configExtensions = $this->config['extensions']['extensions'] ?? [];

        if ($extensionName) {
            if (in_array($extensionName, $configExtensions)) {
                $extensionConfig = $this->extensionConfigManager->getGithubExtensionConfig($extensionName);
                if (!empty($extensionConfig['source'])) {
                    $extensions[$extensionName] = $this->githubDiscovery->getRepositoryVersions($extensionConfig['source']);
                } else {
                    echo "  警告: 扩展 $extensionName 配置不完整\n";
                    return [];
                }
            } else {
                echo "  警告: 扩展 $extensionName 不在配置中\n";
                return [];
            }
        } else {
            foreach ($configExtensions as $extension) {
                echo "  发现扩展: $extension\n";
                $extensionConfig = $this->extensionConfigManager->getGithubExtensionConfig($extension);
                if (!empty($extensionConfig['source'])) {
                    $versions = $this->githubDiscovery->getRepositoryVersions($extensionConfig['source']);
                    if (!empty($versions)) {
                        $extensions[$extension] = $versions;
                        echo "    发现 " . count($versions) . " 个版本\n";
                    } else {
                        echo "    警告: 无法获取扩展 $extension 的版本信息\n";
                    }
                } else {
                    echo "    警告: 扩展 $extension 配置不完整\n";
                }
            }
        }

        return $extensions;
    }

    /**
     * 发现所有版本信息
     *
     * @return array 包含所有类型版本信息的数组
     */
    public function discoverAllVersions()
    {
        return [
            'php' => $this->discoverPhpVersions(),
            'pecl' => $this->discoverPeclVersions(),
            'github' => $this->discoverGithubVersions(),
        ];
    }

    /**
     * 从配置文件获取PHP版本
     *
     * @return array PHP版本数组
     */
    private function getConfigPhpVersions()
    {
        $versions = [];
        $configVersions = $this->extensionConfigManager->getPhpVersions();

        // 新格式：按主版本分组的完整版本列表
        foreach ($configVersions as $versionList) {
            if (is_array($versionList)) {
                $versions = array_merge($versions, $versionList);
            }
        }

        return array_unique($versions);
    }

    /**
     * 格式化版本信息用于显示
     *
     * @param array $versions 版本信息
     * @return string 格式化的版本信息
     */
    public function formatVersionsForDisplay($versions)
    {
        $output = "";

        if (isset($versions['php'])) {
            $output .= "PHP 版本:\n";
            foreach ($versions['php'] as $version) {
                $output .= "  - $version\n";
            }
            $output .= "\n";
        }

        if (isset($versions['pecl'])) {
            $output .= "PECL 扩展版本:\n";
            foreach ($versions['pecl'] as $extension => $extensionVersions) {
                $output .= "  $extension:\n";
                foreach ($extensionVersions as $version) {
                    $output .= "    - $version\n";
                }
            }
            $output .= "\n";
        }

        if (isset($versions['github'])) {
            $output .= "GitHub 扩展版本:\n";
            foreach ($versions['github'] as $extension => $extensionVersions) {
                $output .= "  $extension:\n";
                foreach ($extensionVersions as $version) {
                    $output .= "    - $version\n";
                }
            }
        }

        return $output;
    }
}
