<?php

namespace Mirror\Service;

/**
 * 配置更新服务
 *
 * 负责将发现的版本信息更新到分离的配置文件中
 */
class ConfigUpdateService
{
    private $discoveryService;
    private $extensionConfigManager;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->discoveryService = new VersionDiscoveryService();
        $this->extensionConfigManager = new ExtensionConfigManager();
    }

    /**
     * 更新PHP版本配置
     *
     * @param bool $dryRun 是否为试运行（不实际更新文件）
     * @return bool 是否成功
     */
    public function updatePhpVersions($dryRun = false)
    {
        echo "更新 PHP 版本配置...\n";

        $versions = $this->discoveryService->discoverPhpVersions();
        if (empty($versions)) {
            echo "  错误: 无法获取PHP版本信息\n";
            return false;
        }

        // 按主版本分组
        $groupedVersions = $this->groupVersionsByMajor($versions);

        if ($dryRun) {
            echo "  试运行模式，将要更新的版本:\n";
            foreach ($groupedVersions as $major => $versionList) {
                $count = count($versionList);
                $first = reset($versionList);
                $last = end($versionList);

                if ($count <= 5) {
                    // 版本数量少时显示完整列表
                    echo "    '$major' => ['" . implode("', '", $versionList) . "'],\n";
                } else {
                    // 版本数量多时显示范围和数量
                    echo "    '$major' => ['$first' ... '$last'] (共 $count 个版本),\n";
                }
            }
            return true;
        }

        return $this->extensionConfigManager->savePhpVersions($groupedVersions);
    }

    /**
     * 更新PECL扩展版本配置
     *
     * @param string|null $extensionName 指定扩展名，null表示所有扩展
     * @param bool $dryRun 是否为试运行
     * @return bool 是否成功
     */
    public function updatePeclVersions($extensionName = null, $dryRun = false)
    {
        echo "更新 PECL 扩展版本配置...\n";

        $extensions = $this->discoveryService->discoverPeclVersions($extensionName);
        if (empty($extensions)) {
            echo "  错误: 无法获取PECL扩展版本信息\n";
            return false;
        }

        if ($dryRun) {
            echo "  试运行模式，将要更新的扩展版本:\n";
            foreach ($extensions as $extension => $versions) {
                if (!empty($versions)) {
                    echo "    $extension: " . count($versions) . " 个版本 (" . reset($versions) . " ... " . end($versions) . ")\n";
                }
            }
            return true;
        }

        $success = true;
        foreach ($extensions as $extension => $versions) {
            if (!empty($versions)) {
                if ($this->extensionConfigManager->savePeclExtensionVersions($extension, $versions)) {
                    echo "  更新扩展 $extension: " . count($versions) . " 个版本\n";
                } else {
                    echo "  错误: 更新扩展 $extension 失败\n";
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * 更新GitHub扩展版本配置
     *
     * @param string|null $extensionName 指定扩展名，null表示所有扩展
     * @param bool $dryRun 是否为试运行
     * @return bool 是否成功
     */
    public function updateGithubVersions($extensionName = null, $dryRun = false)
    {
        echo "更新 GitHub 扩展版本配置...\n";

        $extensions = $this->discoveryService->discoverGithubVersions($extensionName);
        if (empty($extensions)) {
            echo "  错误: 无法获取GitHub扩展版本信息\n";
            return false;
        }

        if ($dryRun) {
            echo "  试运行模式，将要更新的扩展版本:\n";
            foreach ($extensions as $extension => $versions) {
                if (!empty($versions)) {
                    // 对于版本数量很多的扩展，只显示范围和数量
                    if (count($versions) > 10) {
                        $firstVersion = reset($versions);
                        $lastVersion = end($versions);
                        echo "    $extension: $firstVersion ... $lastVersion (共 " . count($versions) . " 个版本)\n";
                    } else {
                        echo "    $extension: " . implode(', ', $versions) . "\n";
                    }
                }
            }
            return true;
        }

        $success = true;
        foreach ($extensions as $extension => $versions) {
            if (!empty($versions)) {
                if ($this->extensionConfigManager->saveGithubExtensionVersions($extension, $versions)) {
                    echo "  更新扩展 $extension: " . count($versions) . " 个版本\n";
                } else {
                    echo "  错误: 更新扩展 $extension 失败\n";
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * 更新所有版本配置
     *
     * @param bool $dryRun 是否为试运行
     * @return bool 是否成功
     */
    public function updateAllVersions($dryRun = false)
    {
        echo "更新所有版本配置...\n\n";

        $success = true;

        // 更新PHP版本
        if (!$this->updatePhpVersions($dryRun)) {
            $success = false;
        }
        echo "\n";

        // 更新PECL扩展版本
        if (!$this->updatePeclVersions(null, $dryRun)) {
            $success = false;
        }
        echo "\n";

        // 更新GitHub扩展版本
        if (!$this->updateGithubVersions(null, $dryRun)) {
            $success = false;
        }

        if ($success && !$dryRun) {
            echo "\n所有版本配置更新完成\n";
        }

        return $success;
    }

    /**
     * 按主版本分组版本号
     *
     * @param array $versions 版本数组
     * @return array 分组后的完整版本列表
     */
    private function groupVersionsByMajor($versions)
    {
        $grouped = [];

        foreach ($versions as $version) {
            if (preg_match('/^(\d+\.\d+)\./', $version, $matches)) {
                $major = $matches[1];

                if (!isset($grouped[$major])) {
                    $grouped[$major] = [];
                }

                $grouped[$major][] = $version;
            }
        }

        // 对每个主版本的版本列表进行排序和去重
        foreach ($grouped as $major => $versionList) {
            $grouped[$major] = array_unique($versionList);
            usort($grouped[$major], 'version_compare');
        }

        return $grouped;
    }
}
