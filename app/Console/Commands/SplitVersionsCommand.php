<?php

namespace App\Console\Commands;

use App\Services\ConfigService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class SplitVersionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mirror:split-versions
                            {type=php : 分割类型 (php)}
                            {--dry-run : 试运行模式，不实际执行}
                            {--backup : 分割前备份原文件}
                            {--force : 强制执行，覆盖已存在的文件}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将版本配置按主版本分割为独立文件';

    /**
     * 配置服务
     *
     * @var ConfigService
     */
    protected $configService;

    /**
     * 配置目录
     *
     * @var string
     */
    protected $configDir;

    /**
     * 构造函数
     *
     * @param ConfigService $configService
     */
    public function __construct(ConfigService $configService)
    {
        parent::__construct();
        $this->configService = $configService;
        $this->configDir = base_path('config/mirror');
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $dryRun = $this->option('dry-run');
        $backup = $this->option('backup');
        $force = $this->option('force');

        try {
            switch ($type) {
                case 'php':
                    return $this->splitPhpVersions($dryRun, $backup, $force);

                default:
                    $this->error("未知的分割类型: {$type}");
                    $this->showUsage();
                    return 1;
            }

        } catch (\Exception $e) {
            $this->error("版本分割失败: " . $e->getMessage());
            Log::error('版本分割失败', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * 分割PHP版本配置
     *
     * @param bool $dryRun
     * @param bool $backup
     * @param bool $force
     * @return int
     */
    protected function splitPhpVersions(bool $dryRun, bool $backup, bool $force): int
    {
        $this->info('开始分割 PHP 版本配置...');

        // 确保配置目录存在
        if (!File::exists($this->configDir)) {
            File::makeDirectory($this->configDir, 0755, true);
        }

        $sourceFile = $this->configDir . '/php/versions.php';
        
        // 检查源文件是否存在
        if (!File::exists($sourceFile)) {
            $this->warn("源文件不存在: {$sourceFile}");
            $this->info('尝试从系统配置获取PHP版本信息...');
            
            // 从配置服务获取版本信息
            $versions = $this->getPhpVersionsFromConfig();
            if (empty($versions)) {
                $this->error('无法获取PHP版本信息');
                return 1;
            }
        } else {
            // 加载原始配置
            $config = require $sourceFile;
            $versions = $config['versions'] ?? [];
        }

        if (empty($versions)) {
            $this->error('版本配置为空');
            return 1;
        }

        // 按主版本分组
        $groupedVersions = $this->groupVersionsByMajor($versions);
        
        $this->info("  加载到 " . count($groupedVersions) . " 个主版本");
        $this->info("  总计 " . array_sum(array_map('count', $groupedVersions)) . " 个版本");

        if ($dryRun) {
            $this->info('  试运行模式，将要创建的文件:');
            $this->showSplitPlan($groupedVersions);
            return 0;
        }

        // 执行分割
        $success = $this->executeSplit($groupedVersions, $backup, $force);

        if ($success) {
            $this->info("\nPHP 版本配置分割完成");
            if ($backup && File::exists($sourceFile)) {
                $this->info("原文件已备份为: {$sourceFile}.backup");
            }
            return 0;
        } else {
            $this->error("\n分割过程中出现错误");
            return 1;
        }
    }

    /**
     * 从配置服务获取PHP版本
     *
     * @return array
     */
    protected function getPhpVersionsFromConfig(): array
    {
        // 尝试从不同的配置源获取版本信息
        $sources = [
            'discovered.php.versions',
            'mirror.php.versions',
            'php.versions'
        ];

        foreach ($sources as $source) {
            $versions = $this->configService->get($source, []);
            if (!empty($versions)) {
                $this->info("  从配置 {$source} 获取到 " . count($versions) . " 个版本");
                return is_array($versions) ? $versions : [];
            }
        }

        return [];
    }

    /**
     * 按主版本分组版本
     *
     * @param array $versions
     * @return array
     */
    protected function groupVersionsByMajor(array $versions): array
    {
        $grouped = [];

        foreach ($versions as $version) {
            if (preg_match('/^(\d+\.\d+)\./', $version, $matches)) {
                $majorVersion = $matches[1];
                
                if (!isset($grouped[$majorVersion])) {
                    $grouped[$majorVersion] = [];
                }
                
                $grouped[$majorVersion][] = $version;
            }
        }

        // 对每个主版本的子版本进行排序
        foreach ($grouped as $majorVersion => $versionList) {
            usort($versionList, 'version_compare');
            $grouped[$majorVersion] = array_unique($versionList);
        }

        // 对主版本进行排序
        uksort($grouped, 'version_compare');

        return $grouped;
    }

    /**
     * 显示分割计划
     *
     * @param array $groupedVersions
     */
    protected function showSplitPlan(array $groupedVersions): void
    {
        $baseDir = $this->configDir . '/php';

        foreach ($groupedVersions as $majorVersion => $versionList) {
            $versionCount = count($versionList);
            $this->line("    {$baseDir}/{$majorVersion}/versions.php ({$versionCount} 个版本)");
            $this->line("    {$baseDir}/{$majorVersion}/metadata.php");
        }

        $this->line("    {$baseDir}/index.php (主索引文件)");
    }

    /**
     * 执行分割
     *
     * @param array $groupedVersions
     * @param bool $backup
     * @param bool $force
     * @return bool
     */
    protected function executeSplit(array $groupedVersions, bool $backup, bool $force): bool
    {
        $baseDir = $this->configDir . '/php';
        $sourceFile = $baseDir . '/versions.php';

        // 确保基础目录存在
        if (!File::exists($baseDir)) {
            File::makeDirectory($baseDir, 0755, true);
        }

        // 备份原文件
        if ($backup && File::exists($sourceFile)) {
            if (!File::copy($sourceFile, $sourceFile . '.backup')) {
                $this->error('  错误: 无法备份原文件');
                return false;
            }
        }

        $success = true;

        // 为每个主版本创建目录和文件
        foreach ($groupedVersions as $majorVersion => $versionList) {
            $versionDir = $baseDir . '/' . $majorVersion;

            // 创建版本目录
            if (!File::exists($versionDir)) {
                if (!File::makeDirectory($versionDir, 0755, true)) {
                    $this->error("  错误: 无法创建目录 {$versionDir}");
                    $success = false;
                    continue;
                }
            }

            // 创建版本文件
            if (!$this->createVersionFile($versionDir, $majorVersion, $versionList, $force)) {
                $success = false;
            }

            // 创建元数据文件
            if (!$this->createMetadataFile($versionDir, $majorVersion, $versionList, $force)) {
                $success = false;
            }

            $this->info("  创建 PHP {$majorVersion}: " . count($versionList) . " 个版本");
        }

        // 创建主索引文件
        if (!$this->createIndexFile($baseDir, $groupedVersions, $force)) {
            $success = false;
        }

        return $success;
    }

    /**
     * 创建版本文件
     *
     * @param string $versionDir
     * @param string $majorVersion
     * @param array $versionList
     * @param bool $force
     * @return bool
     */
    protected function createVersionFile(string $versionDir, string $majorVersion, array $versionList, bool $force): bool
    {
        $versionFile = $versionDir . '/versions.php';

        // 检查文件是否已存在
        if (File::exists($versionFile) && !$force) {
            $this->warn("    版本文件已存在: {$versionFile} (使用 --force 覆盖)");
            return true;
        }

        $config = [
            'major_version' => $majorVersion,
            'versions' => $versionList,
            'count' => count($versionList),
            'range' => [
                'min' => reset($versionList),
                'max' => end($versionList)
            ],
            'metadata' => [
                'created_at' => now()->toISOString(),
                'source' => 'split from main versions configuration',
                'auto_generated' => true
            ]
        ];

        return $this->saveConfigFile($versionFile, $config, "PHP {$majorVersion} 版本配置文件");
    }

    /**
     * 创建元数据文件
     *
     * @param string $versionDir
     * @param string $majorVersion
     * @param array $versionList
     * @param bool $force
     * @return bool
     */
    protected function createMetadataFile(string $versionDir, string $majorVersion, array $versionList, bool $force): bool
    {
        $metadataFile = $versionDir . '/metadata.php';

        // 检查文件是否已存在
        if (File::exists($metadataFile) && !$force) {
            $this->warn("    元数据文件已存在: {$metadataFile} (使用 --force 覆盖)");
            return true;
        }

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
                'last_updated' => now()->toISOString(),
                'discovery_source' => 'GitHub API',
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
     * @param string $baseDir
     * @param array $groupedVersions
     * @param bool $force
     * @return bool
     */
    protected function createIndexFile(string $baseDir, array $groupedVersions, bool $force): bool
    {
        $indexFile = $baseDir . '/index.php';

        // 检查文件是否已存在
        if (File::exists($indexFile) && !$force) {
            $this->warn("    索引文件已存在: {$indexFile} (使用 --force 覆盖)");
            return true;
        }

        $majorVersions = [];
        foreach ($groupedVersions as $majorVersion => $versionList) {
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
            'split_date' => now()->toISOString(),
            'major_versions' => $majorVersions,
            'summary' => [
                'total_major_versions' => count($groupedVersions),
                'total_versions' => array_sum(array_map('count', $groupedVersions)),
                'version_range' => [
                    'oldest' => $this->getOldestVersion($groupedVersions),
                    'newest' => $this->getNewestVersion($groupedVersions)
                ]
            ],
            'usage' => [
                'load_all' => 'require_once "index.php"; $allVersions = loadAllPhpVersions();',
                'load_major' => 'require_once "8.3/versions.php"; $php83 = $config["versions"];',
                'get_metadata' => 'require_once "8.3/metadata.php"; $meta = $config;'
            ]
        ];

        return $this->saveConfigFile($indexFile, $config, "PHP 版本主索引文件");
    }

    /**
     * 获取EOL信息
     *
     * @param string $majorVersion
     * @return string|null
     */
    protected function getEolInfo(string $majorVersion): ?string
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
     * @param string $majorVersion
     * @return string
     */
    protected function getVersionStatus(string $majorVersion): string
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
     * @param string $majorVersion
     * @return string
     */
    protected function getSupportLevel(string $majorVersion): string
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
     * @param array $groupedVersions
     * @return string
     */
    protected function getOldestVersion(array $groupedVersions): string
    {
        $allVersions = [];
        foreach ($groupedVersions as $versionList) {
            $allVersions = array_merge($allVersions, $versionList);
        }

        usort($allVersions, 'version_compare');
        return reset($allVersions);
    }

    /**
     * 获取最新版本
     *
     * @param array $groupedVersions
     * @return string
     */
    protected function getNewestVersion(array $groupedVersions): string
    {
        $allVersions = [];
        foreach ($groupedVersions as $versionList) {
            $allVersions = array_merge($allVersions, $versionList);
        }

        usort($allVersions, 'version_compare');
        return end($allVersions);
    }

    /**
     * 保存配置文件
     *
     * @param string $filePath
     * @param array $config
     * @param string $description
     * @return bool
     */
    protected function saveConfigFile(string $filePath, array $config, string $description): bool
    {
        try {
            $content = "<?php\n\n";
            $content .= "/**\n";
            $content .= " * {$description}\n";
            $content .= " * \n";
            $content .= " * 此文件由版本分割工具自动生成\n";
            $content .= " * 生成时间: " . now()->format('Y-m-d H:i:s') . "\n";
            $content .= " */\n\n";
            $content .= "return " . $this->arrayToPhpCode($config, 0) . ";\n";

            $result = File::put($filePath, $content);
            return $result !== false;

        } catch (\Exception $e) {
            $this->error("  错误: 保存文件失败 {$filePath}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 将数组转换为PHP代码
     *
     * @param mixed $data
     * @param int $indent
     * @return string
     */
    protected function arrayToPhpCode($data, int $indent = 0): string
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
     * 显示使用说明
     */
    protected function showUsage(): void
    {
        $this->line('可用类型: php');
        $this->newLine();
        $this->line('使用示例:');
        $this->line('  php artisan mirror:split-versions php          # 分割PHP版本配置');
        $this->line('  php artisan mirror:split-versions php --dry-run # 试运行模式');
        $this->line('  php artisan mirror:split-versions php --backup  # 分割前备份');
        $this->line('  php artisan mirror:split-versions php --force   # 强制覆盖已存在文件');
    }
}
