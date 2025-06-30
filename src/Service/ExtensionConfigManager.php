<?php

namespace Mirror\Service;

/**
 * 扩展配置管理服务
 *
 * 负责管理分离的扩展配置文件
 */
class ExtensionConfigManager
{
    private $configDir;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->configDir = ROOT_DIR . '/config';
    }

    /**
     * 获取PHP版本配置
     *
     * @return array PHP版本配置
     */
    public function getPhpVersions()
    {
        // 检查是否已拆分为独立文件
        $indexFile = $this->configDir . '/extensions/php/index.php';
        if (file_exists($indexFile)) {
            return $this->getPhpVersionsFromSplitFiles();
        }

        // 使用原始单文件格式
        $configFile = $this->configDir . '/extensions/php/versions.php';
        if (!file_exists($configFile)) {
            return [];
        }

        $config = require $configFile;
        return $config['versions'] ?? [];
    }

    /**
     * 从拆分文件中获取PHP版本配置
     *
     * @return array 版本配置
     */
    private function getPhpVersionsFromSplitFiles()
    {
        $indexFile = $this->configDir . '/extensions/php/index.php';
        $index = require $indexFile;

        $versions = [];

        if (isset($index['major_versions'])) {
            foreach ($index['major_versions'] as $majorVersion => $info) {
                $versionFile = $this->configDir . '/extensions/php/' . $info['config_file'];
                if (file_exists($versionFile)) {
                    $versionConfig = require $versionFile;
                    $versions[$majorVersion] = $versionConfig['versions'] ?? [];
                }
            }
        }

        return $versions;
    }

    /**
     * 获取Composer版本配置
     *
     * @return array Composer版本配置
     */
    public function getComposerVersions()
    {
        $configFile = $this->configDir . '/composer/versions.php';

        if (!file_exists($configFile)) {
            return [];
        }

        $config = require $configFile;
        return $config['versions'] ?? [];
    }

    /**
     * 获取PECL扩展版本配置
     *
     * @param string $extensionName 扩展名
     * @return array 扩展版本配置
     */
    public function getPeclExtensionVersions($extensionName)
    {
        $configFile = $this->configDir . "/extensions/pecl/{$extensionName}.php";

        if (!file_exists($configFile)) {
            return [];
        }

        $config = require $configFile;
        return $config['recommended_versions'] ?? $config['all_versions'] ?? [];
    }

    /**
     * 获取GitHub扩展版本配置
     *
     * @param string $extensionName 扩展名
     * @return array 扩展版本配置
     */
    public function getGithubExtensionVersions($extensionName)
    {
        $configFile = $this->configDir . "/extensions/github/{$extensionName}.php";

        if (!file_exists($configFile)) {
            return [];
        }

        $config = require $configFile;
        return $config['recommended_versions'] ?? $config['all_versions'] ?? [];
    }

    /**
     * 获取GitHub扩展配置
     *
     * @param string $extensionName 扩展名
     * @return array 扩展配置
     */
    public function getGithubExtensionConfig($extensionName)
    {
        $configFile = $this->configDir . "/extensions/github/{$extensionName}.php";

        if (!file_exists($configFile)) {
            return [];
        }

        return require $configFile;
    }

    /**
     * 获取所有PECL扩展的版本配置
     *
     * @return array 所有PECL扩展版本配置
     */
    public function getAllPeclExtensionVersions()
    {
        $peclDir = $this->configDir . '/extensions/pecl';
        $extensions = [];

        if (!is_dir($peclDir)) {
            return $extensions;
        }

        $files = glob($peclDir . '/*.php');
        foreach ($files as $file) {
            $extensionName = basename($file, '.php');
            $config = require $file;
            $extensions[$extensionName] = $config['recommended_versions'] ?? $config['all_versions'] ?? [];
        }

        return $extensions;
    }

    /**
     * 获取所有GitHub扩展的版本配置
     *
     * @return array 所有GitHub扩展版本配置
     */
    public function getAllGithubExtensionVersions()
    {
        $githubDir = $this->configDir . '/extensions/github';
        $extensions = [];

        if (!is_dir($githubDir)) {
            return $extensions;
        }

        $files = glob($githubDir . '/*.php');
        foreach ($files as $file) {
            $extensionName = basename($file, '.php');
            $config = require $file;
            $extensions[$extensionName] = $config['recommended_versions'] ?? $config['all_versions'] ?? [];
        }

        return $extensions;
    }

    /**
     * 保存PHP版本配置
     *
     * @param array $versions 版本配置（按主版本分组）
     * @return bool 是否成功
     */
    public function savePhpVersions($versions)
    {
        // 检查是否已拆分为独立文件
        $indexFile = $this->configDir . '/extensions/php/index.php';
        if (file_exists($indexFile)) {
            return $this->savePhpVersionsToSplitFiles($versions);
        }

        // 使用原始单文件格式
        $configFile = $this->configDir . '/extensions/php/versions.php';
        $config = $this->loadConfigFile($configFile);

        $config['versions'] = $versions;
        $config['metadata']['last_updated'] = date('Y-m-d H:i:s');
        $config['metadata']['auto_updated'] = true;

        // 计算总版本数（所有主版本的版本数之和）
        $totalVersions = 0;
        foreach ($versions as $versionList) {
            $totalVersions += count($versionList);
        }
        $config['metadata']['total_versions'] = $totalVersions;

        return $this->saveConfigFile($configFile, $config);
    }

    /**
     * 保存PHP版本配置到拆分文件
     *
     * @param array $versions 版本配置（按主版本分组）
     * @return bool 是否成功
     */
    private function savePhpVersionsToSplitFiles($versions)
    {
        $success = true;
        $baseDir = $this->configDir . '/extensions/php';

        // 更新每个主版本的文件
        foreach ($versions as $majorVersion => $versionList) {
            $versionDir = $baseDir . '/' . $majorVersion;

            // 确保目录存在
            if (!is_dir($versionDir)) {
                if (!mkdir($versionDir, 0755, true)) {
                    echo "  错误: 无法创建目录 $versionDir\n";
                    $success = false;
                    continue;
                }
            }

            // 更新版本文件
            if (!$this->updateVersionFile($versionDir, $majorVersion, $versionList)) {
                $success = false;
            }

            // 更新元数据文件
            if (!$this->updateMetadataFile($versionDir, $majorVersion, $versionList)) {
                $success = false;
            }
        }

        // 更新主索引文件
        if (!$this->updateIndexFile($baseDir, $versions)) {
            $success = false;
        }

        return $success;
    }

    /**
     * 保存Composer版本配置
     *
     * @param array $versions 版本配置
     * @return bool 是否成功
     */
    public function saveComposerVersions($versions)
    {
        $configFile = $this->configDir . '/composer/versions.php';
        $config = $this->loadConfigFile($configFile);

        $config['versions'] = $versions;
        $config['metadata']['last_updated'] = date('Y-m-d H:i:s');
        $config['metadata']['auto_updated'] = true;
        $config['metadata']['total_versions'] = count($versions);

        return $this->saveConfigFile($configFile, $config);
    }

    /**
     * 保存PECL扩展版本配置
     *
     * @param string $extensionName 扩展名
     * @param array $versions 版本配置
     * @return bool 是否成功
     */
    public function savePeclExtensionVersions($extensionName, $versions)
    {
        $configFile = $this->configDir . "/extensions/pecl/{$extensionName}.php";
        $config = $this->loadConfigFile($configFile);

        $config['all_versions'] = $versions;
        $config['recommended_versions'] = $this->selectRecommendedVersions($versions);
        $config['metadata']['last_updated'] = date('Y-m-d H:i:s');
        $config['metadata']['auto_updated'] = true;
        $config['metadata']['total_discovered'] = count($versions);
        $config['metadata']['total_recommended'] = count($config['recommended_versions']);

        return $this->saveConfigFile($configFile, $config);
    }

    /**
     * 保存GitHub扩展版本配置
     *
     * @param string $extensionName 扩展名
     * @param array $versions 版本配置
     * @return bool 是否成功
     */
    public function saveGithubExtensionVersions($extensionName, $versions)
    {
        $configFile = $this->configDir . "/extensions/github/{$extensionName}.php";
        $config = $this->loadConfigFile($configFile);

        $config['all_versions'] = $versions;
        $config['recommended_versions'] = $this->selectRecommendedVersions($versions);
        $config['metadata']['last_updated'] = date('Y-m-d H:i:s');
        $config['metadata']['auto_updated'] = true;
        $config['metadata']['total_discovered'] = count($versions);
        $config['metadata']['total_recommended'] = count($config['recommended_versions']);

        return $this->saveConfigFile($configFile, $config);
    }

    /**
     * 加载配置文件
     *
     * @param string $configFile 配置文件路径
     * @return array 配置数组
     */
    private function loadConfigFile($configFile)
    {
        if (file_exists($configFile)) {
            return require $configFile;
        }

        return [];
    }

    /**
     * 保存配置文件
     *
     * @param string $configFile 配置文件路径
     * @param array $config 配置数组
     * @return bool 是否成功
     */
    private function saveConfigFile($configFile, $config)
    {
        try {
            // 确保目录存在
            $dir = dirname($configFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $content = "<?php\n\n";
            $content .= "/**\n";
            $content .= " * " . ($config['name'] ?? 'Extension') . " 版本配置文件\n";
            $content .= " * \n";
            $content .= " * 此文件由版本发现服务自动更新\n";
            $content .= " * 最后更新时间: " . ($config['metadata']['last_updated'] ?? date('Y-m-d H:i:s')) . "\n";
            $content .= " */\n\n";
            $content .= "return " . $this->arrayToPhpCode($config, 0) . ";\n";

            $result = file_put_contents($configFile, $content);

            return $result !== false;

        } catch (\Exception $e) {
            echo "  错误: 保存配置文件失败: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * 选择推荐版本
     *
     * @param array $versions 所有版本
     * @return array 推荐版本
     */
    private function selectRecommendedVersions($versions)
    {
        // 如果版本数量少于等于10个，全部推荐
        if (count($versions) <= 10) {
            return $versions;
        }

        // 否则使用智能选择
        return $this->selectSmartVersions($versions);
    }

    /**
     * 智能选择版本
     *
     * @param array $versions 所有版本
     * @return array 智能选择的版本
     */
    private function selectSmartVersions($versions)
    {
        // 按主版本分组
        $grouped = [];
        foreach ($versions as $version) {
            if (preg_match('/^(\d+)\./', $version, $matches)) {
                $major = $matches[1];
                if (!isset($grouped[$major])) {
                    $grouped[$major] = [];
                }
                $grouped[$major][] = $version;
            }
        }

        $selected = [];

        // 对每个主版本，选择最新的几个版本
        foreach ($grouped as $major => $majorVersions) {
            usort($majorVersions, 'version_compare');
            $latestVersions = array_slice($majorVersions, -3);
            $selected = array_merge($selected, $latestVersions);
        }

        usort($selected, 'version_compare');
        return $selected;
    }

    /**
     * 将数组转换为PHP代码
     *
     * @param mixed $data 数据
     * @param int $indent 缩进级别
     * @return string PHP代码
     */
    private function arrayToPhpCode($data, $indent = 0)
    {
        $spaces = str_repeat('    ', $indent);

        if (is_array($data)) {
            $result = "[\n";
            foreach ($data as $key => $value) {
                $result .= $spaces . '    ';
                if (is_string($key)) {
                    $result .= "'" . addslashes($key) . "' => ";
                }
                $result .= $this->arrayToPhpCode($value, $indent + 1);
                $result .= ",\n";
            }
            $result .= $spaces . "]";
            return $result;
        } elseif (is_string($data)) {
            return "'" . addslashes($data) . "'";
        } elseif (is_bool($data)) {
            return $data ? 'true' : 'false';
        } elseif (is_null($data)) {
            return 'null';
        } else {
            return (string)$data;
        }
    }

    /**
     * 更新版本文件
     *
     * @param string $versionDir 版本目录
     * @param string $majorVersion 主版本号
     * @param array $versionList 版本列表
     * @return bool 是否成功
     */
    private function updateVersionFile($versionDir, $majorVersion, $versionList)
    {
        $versionFile = $versionDir . '/versions.php';

        // 加载现有配置（如果存在）
        $config = [];
        if (file_exists($versionFile)) {
            $config = require $versionFile;
        }

        // 更新版本信息
        $config['major_version'] = $majorVersion;
        $config['versions'] = $versionList;
        $config['count'] = count($versionList);
        $config['range'] = [
            'min' => reset($versionList),
            'max' => end($versionList)
        ];
        $config['metadata']['last_updated'] = date('Y-m-d H:i:s');
        $config['metadata']['source'] = 'version discovery service';
        $config['metadata']['auto_generated'] = true;

        return $this->saveSplitConfigFile($versionFile, $config, "PHP {$majorVersion} 版本配置文件");
    }

    /**
     * 更新元数据文件
     *
     * @param string $versionDir 版本目录
     * @param string $majorVersion 主版本号
     * @param array $versionList 版本列表
     * @return bool 是否成功
     */
    private function updateMetadataFile($versionDir, $majorVersion, $versionList)
    {
        $metadataFile = $versionDir . '/metadata.php';

        // 加载现有元数据（如果存在）
        $metadata = [];
        if (file_exists($metadataFile)) {
            $metadata = require $metadataFile;
        }

        // 更新元数据
        $metadata['major_version'] = $majorVersion;
        $metadata['version_count'] = count($versionList);
        $metadata['first_version'] = reset($versionList);
        $metadata['latest_version'] = end($versionList);
        $metadata['release_info'] = [
            'eol' => $this->getEolInfo($majorVersion),
            'status' => $this->getVersionStatus($majorVersion),
            'support_level' => $this->getSupportLevel($majorVersion)
        ];
        $metadata['discovery'] = [
            'last_updated' => date('Y-m-d H:i:s'),
            'discovery_source' => 'GitHub API',
            'auto_updated' => true
        ];
        $metadata['files'] = [
            'download_pattern' => "php-{version}.tar.gz",
            'estimated_size_mb' => 20,
            'compression' => 'gzip'
        ];

        return $this->saveSplitConfigFile($metadataFile, $metadata, "PHP {$majorVersion} 元数据文件");
    }

    /**
     * 更新主索引文件
     *
     * @param string $baseDir 基础目录
     * @param array $versions 版本配置
     * @return bool 是否成功
     */
    private function updateIndexFile($baseDir, $versions)
    {
        $indexFile = $baseDir . '/index.php';

        // 加载现有索引（如果存在）
        $index = [];
        if (file_exists($indexFile)) {
            $index = require $indexFile;
        }

        $majorVersions = [];
        foreach ($versions as $majorVersion => $versionList) {
            $majorVersions[$majorVersion] = [
                'version_count' => count($versionList),
                'first_version' => reset($versionList),
                'latest_version' => end($versionList),
                'config_file' => "{$majorVersion}/versions.php",
                'metadata_file' => "{$majorVersion}/metadata.php"
            ];
        }

        $index['structure_version'] = '2.0';
        $index['last_updated'] = date('Y-m-d H:i:s');
        $index['major_versions'] = $majorVersions;
        $index['summary'] = [
            'total_major_versions' => count($versions),
            'total_versions' => array_sum(array_map('count', $versions)),
            'version_range' => [
                'oldest' => $this->getOldestVersion($versions),
                'newest' => $this->getNewestVersion($versions)
            ]
        ];
        $index['global_metadata'] = [
            'total_versions' => array_sum(array_map('count', $versions)),
            'last_updated' => date('Y-m-d H:i:s'),
            'discovery_source' => 'GitHub API',
            'auto_updated' => true
        ];

        return $this->saveSplitConfigFile($indexFile, $index, "PHP 版本主索引文件");
    }

    /**
     * 保存拆分配置文件
     *
     * @param string $filePath 文件路径
     * @param array $config 配置数组
     * @param string $description 文件描述
     * @return bool 是否成功
     */
    private function saveSplitConfigFile($filePath, $config, $description)
    {
        try {
            $content = "<?php\n\n";
            $content .= "/**\n";
            $content .= " * {$description}\n";
            $content .= " * \n";
            $content .= " * 此文件由版本发现服务自动更新\n";
            $content .= " * 最后更新时间: " . date('Y-m-d H:i:s') . "\n";
            $content .= " */\n\n";
            $content .= "return " . $this->arrayToPhpCode($config, 0) . ";\n";

            $result = file_put_contents($filePath, $content);
            return $result !== false;

        } catch (\Exception $e) {
            echo "  错误: 保存文件失败 {$filePath}: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * 获取 EOL 信息
     *
     * @param string $majorVersion 主版本号
     * @return string|null EOL 日期
     */
    private function getEolInfo($majorVersion)
    {
        $eolDates = [
            '5.4' => '2015-09-14',
            '5.5' => '2016-07-21',
            '5.6' => '2018-12-31',
            '7.0' => '2019-01-10',
            '7.1' => '2019-12-01',
            '7.2' => '2020-11-30',
            '7.3' => '2021-12-06',
            '7.4' => '2022-11-28',
            '8.0' => '2023-11-26',
            '8.1' => '2024-11-25',
            '8.2' => '2025-12-08',
            '8.3' => '2026-11-23',
            '8.4' => null // 未来版本
        ];

        return $eolDates[$majorVersion] ?? null;
    }

    /**
     * 获取版本状态
     *
     * @param string $majorVersion 主版本号
     * @return string 版本状态
     */
    private function getVersionStatus($majorVersion)
    {
        $currentYear = date('Y');
        $eolDate = $this->getEolInfo($majorVersion);

        if (!$eolDate) {
            return 'active'; // 未来版本
        }

        $eolYear = date('Y', strtotime($eolDate));

        if ($eolYear < $currentYear) {
            return 'end-of-life';
        } elseif ($eolYear == $currentYear) {
            return 'security-only';
        } else {
            return 'active';
        }
    }

    /**
     * 获取支持级别
     *
     * @param string $majorVersion 主版本号
     * @return string 支持级别
     */
    private function getSupportLevel($majorVersion)
    {
        $status = $this->getVersionStatus($majorVersion);

        switch ($status) {
            case 'active':
                return 'full';
            case 'security-only':
                return 'security';
            case 'end-of-life':
                return 'none';
            default:
                return 'unknown';
        }
    }

    /**
     * 获取最旧版本
     *
     * @param array $versions 版本配置
     * @return string 最旧版本
     */
    private function getOldestVersion($versions)
    {
        $allVersions = [];
        foreach ($versions as $versionList) {
            $allVersions = array_merge($allVersions, $versionList);
        }

        usort($allVersions, 'version_compare');
        return reset($allVersions);
    }

    /**
     * 获取最新版本
     *
     * @param array $versions 版本配置
     * @return string 最新版本
     */
    private function getNewestVersion($versions)
    {
        $allVersions = [];
        foreach ($versions as $versionList) {
            $allVersions = array_merge($allVersions, $versionList);
        }

        usort($allVersions, 'version_compare');
        return end($allVersions);
    }
}
