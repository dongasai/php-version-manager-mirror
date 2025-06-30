<?php

namespace Mirror\Command;

/**
 * 拆分版本配置命令
 */
class SplitVersionsCommand extends AbstractCommand
{
    private $configDir;

    public function __construct()
    {
        parent::__construct('split-versions', '拆分版本配置为独立文件');
        $this->configDir = dirname(__DIR__, 2) . '/config';
    }

    /**
     * 执行拆分命令
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    public function execute(array $args = [])
    {
        $type = isset($args[0]) ? $args[0] : 'php';
        $dryRun = in_array('--dry-run', $args);

        switch ($type) {
            case 'php':
                return $this->splitPhpVersions($dryRun);

            default:
                echo "错误: 未知的拆分类型 '$type'\n";
                echo "可用类型: php\n";
                echo "\n使用示例:\n";
                echo "  ./bin/pvm-mirror split-versions php          # 拆分PHP版本配置\n";
                echo "  ./bin/pvm-mirror split-versions php --dry-run # 试运行模式\n";
                return 1;
        }
    }

    /**
     * 拆分 PHP 版本配置
     *
     * @param bool $dryRun 是否为试运行
     * @return int 退出代码
     */
    private function splitPhpVersions($dryRun = false)
    {
        echo "开始拆分 PHP 版本配置...\n";

        $sourceFile = $this->configDir . '/extensions/php/versions.php';

        if (!file_exists($sourceFile)) {
            echo "  错误: 源文件不存在: $sourceFile\n";
            return 1;
        }

        // 加载原始配置
        $config = require $sourceFile;
        $versions = $config['versions'] ?? [];
        $metadata = $config['metadata'] ?? [];

        if (empty($versions)) {
            echo "  错误: 版本配置为空\n";
            return 1;
        }

        echo "  加载到 " . count($versions) . " 个主版本\n";

        if ($dryRun) {
            echo "  试运行模式，将要创建的文件:\n";
            $this->showSplitPlan($versions);
            return 0;
        }

        // 执行拆分
        $success = $this->executeSplit($versions, $metadata);

        if ($success) {
            echo "\nPHP 版本配置拆分完成\n";
            echo "原文件已备份为: {$sourceFile}.backup\n";
            return 0;
        } else {
            echo "\n拆分过程中出现错误\n";
            return 1;
        }
    }

    /**
     * 显示拆分计划
     *
     * @param array $versions 版本配置
     */
    private function showSplitPlan($versions)
    {
        $baseDir = $this->configDir . '/extensions/php';

        foreach ($versions as $majorVersion => $versionList) {
            $versionCount = count($versionList);
            echo "    {$baseDir}/{$majorVersion}/versions.php ({$versionCount} 个版本)\n";
            echo "    {$baseDir}/{$majorVersion}/metadata.php\n";
        }

        echo "    {$baseDir}/index.php (主索引文件)\n";
    }

    /**
     * 执行拆分
     *
     * @param array $versions 版本配置
     * @param array $metadata 元数据
     * @return bool 是否成功
     */
    private function executeSplit($versions, $metadata)
    {
        $baseDir = $this->configDir . '/extensions/php';
        $sourceFile = $baseDir . '/versions.php';

        // 备份原文件
        if (!copy($sourceFile, $sourceFile . '.backup')) {
            echo "  错误: 无法备份原文件\n";
            return false;
        }

        $success = true;

        // 为每个主版本创建目录和文件
        foreach ($versions as $majorVersion => $versionList) {
            $versionDir = $baseDir . '/' . $majorVersion;

            // 创建版本目录
            if (!is_dir($versionDir)) {
                if (!mkdir($versionDir, 0755, true)) {
                    echo "  错误: 无法创建目录 $versionDir\n";
                    $success = false;
                    continue;
                }
            }

            // 创建版本文件
            if (!$this->createVersionFile($versionDir, $majorVersion, $versionList)) {
                $success = false;
            }

            // 创建元数据文件
            if (!$this->createMetadataFile($versionDir, $majorVersion, $versionList, $metadata)) {
                $success = false;
            }

            echo "  创建 PHP {$majorVersion}: " . count($versionList) . " 个版本\n";
        }

        // 创建主索引文件
        if (!$this->createIndexFile($baseDir, $versions, $metadata)) {
            $success = false;
        }

        return $success;
    }

    /**
     * 创建版本文件
     *
     * @param string $versionDir 版本目录
     * @param string $majorVersion 主版本号
     * @param array $versionList 版本列表
     * @return bool 是否成功
     */
    private function createVersionFile($versionDir, $majorVersion, $versionList)
    {
        $versionFile = $versionDir . '/versions.php';

        $config = [
            'major_version' => $majorVersion,
            'versions' => $versionList,
            'count' => count($versionList),
            'range' => [
                'min' => reset($versionList),
                'max' => end($versionList)
            ],
            'metadata' => [
                'created_at' => date('Y-m-d H:i:s'),
                'source' => 'split from main versions.php',
                'auto_generated' => true
            ]
        ];

        return $this->saveConfigFile($versionFile, $config, "PHP {$majorVersion} 版本配置文件");
    }

    /**
     * 创建元数据文件
     *
     * @param string $versionDir 版本目录
     * @param string $majorVersion 主版本号
     * @param array $versionList 版本列表
     * @param array $globalMetadata 全局元数据
     * @return bool 是否成功
     */
    private function createMetadataFile($versionDir, $majorVersion, $versionList, $globalMetadata)
    {
        $metadataFile = $versionDir . '/metadata.php';

        $metadata = [
            'major_version' => $majorVersion,
            'version_count' => count($versionList),
            'first_version' => reset($versionList),
            'latest_version' => end($versionList),
            'release_info' => [
                'eol' => $this->getEolInfo($majorVersion),
                'status' => $this->getVersionStatus($majorVersion),
                'support_level' => $this->getSupportLevel($majorVersion)
            ],
            'discovery' => [
                'last_updated' => $globalMetadata['last_updated'] ?? date('Y-m-d H:i:s'),
                'discovery_source' => $globalMetadata['discovery_source'] ?? 'GitHub API',
                'auto_updated' => true
            ],
            'files' => [
                'download_pattern' => "php-{version}.tar.gz",
                'estimated_size_mb' => 20,
                'compression' => 'gzip'
            ]
        ];

        return $this->saveConfigFile($metadataFile, $metadata, "PHP {$majorVersion} 元数据文件");
    }

    /**
     * 创建主索引文件
     *
     * @param string $baseDir 基础目录
     * @param array $versions 版本配置
     * @param array $globalMetadata 全局元数据
     * @return bool 是否成功
     */
    private function createIndexFile($baseDir, $versions, $globalMetadata)
    {
        $indexFile = $baseDir . '/index.php';

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

        $config = [
            'structure_version' => '2.0',
            'split_date' => date('Y-m-d H:i:s'),
            'major_versions' => $majorVersions,
            'summary' => [
                'total_major_versions' => count($versions),
                'total_versions' => array_sum(array_map('count', $versions)),
                'version_range' => [
                    'oldest' => $this->getOldestVersion($versions),
                    'newest' => $this->getNewestVersion($versions)
                ]
            ],
            'global_metadata' => $globalMetadata,
            'usage' => [
                'load_all' => 'require_once "index.php"; $allVersions = loadAllPhpVersions();',
                'load_major' => 'require_once "8.3/versions.php"; $php83 = $config["versions"];',
                'get_metadata' => 'require_once "8.3/metadata.php"; $meta = $config;'
            ]
        ];

        return $this->saveConfigFile($indexFile, $config, "PHP 版本主索引文件");
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

    /**
     * 保存配置文件
     *
     * @param string $filePath 文件路径
     * @param array $config 配置数组
     * @param string $description 文件描述
     * @return bool 是否成功
     */
    private function saveConfigFile($filePath, $config, $description)
    {
        try {
            $content = "<?php\n\n";
            $content .= "/**\n";
            $content .= " * {$description}\n";
            $content .= " * \n";
            $content .= " * 此文件由版本拆分工具自动生成\n";
            $content .= " * 生成时间: " . date('Y-m-d H:i:s') . "\n";
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
}
