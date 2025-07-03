<?php

namespace App\Console\Commands;

use App\Services\ConfigService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscoverCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mirror:discover
                            {type? : 发现类型 (php, pecl, github, ext, all)}
                            {target? : 指定目标 (仅对pecl和github有效)}
                            {--json : 以JSON格式输出结果}
                            {--save : 保存发现的版本到配置}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '发现可用版本信息';

    /**
     * 配置服务
     *
     * @var ConfigService
     */
    protected $configService;

    /**
     * 构造函数
     *
     * @param ConfigService $configService
     */
    public function __construct(ConfigService $configService)
    {
        parent::__construct();
        $this->configService = $configService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type') ?? 'all';
        $target = $this->argument('target');
        $jsonOutput = $this->option('json');
        $saveResults = $this->option('save');

        $results = [];

        try {
            switch ($type) {
                case 'php':
                    $results['php'] = $this->discoverPhpVersions();
                    break;

                case 'pecl':
                    $results['pecl'] = $this->discoverPeclVersions($target);
                    break;

                case 'github':
                case 'ext':
                    $results['github'] = $this->discoverGithubVersions($target);
                    break;

                case 'all':
                    $this->info('开始发现所有可用版本...');
                    $this->newLine();
                    
                    $results['php'] = $this->discoverPhpVersions();
                    $results['pecl'] = $this->discoverPeclVersions();
                    $results['github'] = $this->discoverGithubVersions();
                    break;

                default:
                    $this->error("未知的发现类型: {$type}");
                    $this->showUsage();
                    return 1;
            }

            // 输出结果
            if ($jsonOutput) {
                $this->line(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } else {
                $this->displayResults($results);
            }

            // 保存结果
            if ($saveResults) {
                $this->saveDiscoveredVersions($results);
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("版本发现失败: " . $e->getMessage());
            Log::error('版本发现失败', [
                'type' => $type,
                'target' => $target,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * 发现PHP版本
     *
     * @return array
     */
    protected function discoverPhpVersions(): array
    {
        $this->info('正在发现 PHP 版本...');

        try {
            // 首先从硬编码配置获取现有版本
            $existingVersions = $this->configService->getPhpVersions();
            $allVersions = [];

            // 收集所有现有版本
            foreach ($existingVersions as $majorVersion => $config) {
                if (is_array($config) && isset($config['versions'])) {
                    $allVersions = array_merge($allVersions, $config['versions']);
                }
            }

            $this->info("  从配置文件加载了 " . count($allVersions) . " 个现有版本");

            // 从GitHub API获取最新版本
            $newVersions = $this->getPhpVersionsFromGithub();

            if (empty($newVersions)) {
                $this->warn('  无法从GitHub获取PHP版本，尝试官方API...');
                $newVersions = $this->getPhpVersionsFromOfficialApi();
            }

            if (!empty($newVersions)) {
                // 合并新版本和现有版本
                $allVersions = array_unique(array_merge($allVersions, $newVersions));
                $this->info("  发现 " . count($newVersions) . " 个新版本");
            } else {
                $this->warn('  无法获取新版本，使用现有配置');
            }

            $this->info("  总计 " . count($allVersions) . " 个 PHP 版本");
            return $allVersions;

        } catch (\Exception $e) {
            $this->error("  PHP版本发现失败: " . $e->getMessage());

            // 发生错误时，返回硬编码配置中的版本
            $existingVersions = $this->configService->getPhpVersions();
            $allVersions = [];
            foreach ($existingVersions as $config) {
                if (is_array($config) && isset($config['versions'])) {
                    $allVersions = array_merge($allVersions, $config['versions']);
                }
            }

            $this->warn("  使用配置文件中的 " . count($allVersions) . " 个版本");
            return $allVersions;
        }
    }

    /**
     * 发现PECL扩展版本
     *
     * @param string|null $extensionName
     * @return array
     */
    protected function discoverPeclVersions(?string $extensionName = null): array
    {
        if ($extensionName) {
            $this->info("正在发现 PECL 扩展 '{$extensionName}' 的版本...");
        } else {
            $this->info('正在发现所有 PECL 扩展版本...');
        }

        try {
            $extensions = [];
            // 从硬编码配置获取支持的扩展列表
            $configExtensions = $this->configService->getMirrorConfig('pecl.extensions', []);

            if ($extensionName) {
                if (in_array($extensionName, $configExtensions)) {
                    $versions = $this->getPeclExtensionVersions($extensionName);
                    if (!empty($versions)) {
                        $extensions[$extensionName] = $versions;
                        $this->info("  发现 " . count($versions) . " 个版本");
                    } else {
                        $this->warn("  无法获取扩展 {$extensionName} 的版本信息");
                    }
                } else {
                    $this->warn("  扩展 {$extensionName} 不在配置中");
                    return [];
                }
            } else {
                foreach ($configExtensions as $extension) {
                    $this->line("  发现扩展: {$extension}");
                    $versions = $this->getPeclExtensionVersions($extension);
                    if (!empty($versions)) {
                        $extensions[$extension] = $versions;
                        $this->info("    发现 " . count($versions) . " 个版本");
                    } else {
                        $this->warn("    无法获取扩展 {$extension} 的版本信息");
                    }
                }
            }

            return $extensions;

        } catch (\Exception $e) {
            $this->error("  PECL扩展版本发现失败: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 发现GitHub扩展版本
     *
     * @param string|null $extensionName
     * @return array
     */
    protected function discoverGithubVersions(?string $extensionName = null): array
    {
        if ($extensionName) {
            $this->info("正在发现 GitHub 扩展 '{$extensionName}' 的版本...");
        } else {
            $this->info('正在发现所有 GitHub 扩展版本...');
        }

        try {
            $extensions = [];
            $configExtensions = $this->configService->get('mirror.extensions.extensions', []);

            if ($extensionName) {
                if (in_array($extensionName, $configExtensions)) {
                    $extensionConfig = $this->getGithubExtensionConfig($extensionName);
                    if (!empty($extensionConfig['source'])) {
                        $versions = $this->getGithubRepositoryVersions($extensionConfig['source']);
                        if (!empty($versions)) {
                            $extensions[$extensionName] = $versions;
                            $this->info("  发现 " . count($versions) . " 个版本");
                        } else {
                            $this->warn("  无法获取扩展 {$extensionName} 的版本信息");
                        }
                    } else {
                        $this->warn("  扩展 {$extensionName} 配置不完整");
                        return [];
                    }
                } else {
                    $this->warn("  扩展 {$extensionName} 不在配置中");
                    return [];
                }
            } else {
                foreach ($configExtensions as $extension) {
                    $this->line("  发现扩展: {$extension}");
                    $extensionConfig = $this->getGithubExtensionConfig($extension);
                    if (!empty($extensionConfig['source'])) {
                        $versions = $this->getGithubRepositoryVersions($extensionConfig['source']);
                        if (!empty($versions)) {
                            $extensions[$extension] = $versions;
                            $this->info("    发现 " . count($versions) . " 个版本");
                        } else {
                            $this->warn("    无法获取扩展 {$extension} 的版本信息");
                        }
                    } else {
                        $this->warn("    扩展 {$extension} 配置不完整");
                    }
                }
            }

            return $extensions;

        } catch (\Exception $e) {
            $this->error("  GitHub扩展版本发现失败: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 从GitHub获取PHP版本
     *
     * @return array
     */
    protected function getPhpVersionsFromGithub(): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'PVM-Mirror/1.0'])
                ->get('https://api.github.com/repos/php/php-src/tags', [
                    'per_page' => 100
                ]);

            if (!$response->successful()) {
                return [];
            }

            $tags = $response->json();
            $versions = [];

            foreach ($tags as $tag) {
                $tagName = $tag['name'];
                
                // 匹配 PHP 版本格式：php-x.y.z
                if (preg_match('/^php-(\d+\.\d+\.\d+)$/', $tagName, $matches)) {
                    $version = $matches[1];
                    
                    // 过滤掉太老的版本（5.4 以下）
                    if (version_compare($version, '5.4.0', '>=')) {
                        $versions[] = $version;
                    }
                }
            }

            // 排序版本
            usort($versions, 'version_compare');
            
            return array_unique($versions);

        } catch (\Exception $e) {
            Log::error('从GitHub获取PHP版本失败', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * 从官方API获取PHP版本
     *
     * @return array
     */
    protected function getPhpVersionsFromOfficialApi(): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'PVM-Mirror/1.0'])
                ->get('https://www.php.net/releases/index.php?json&version=8');

            if (!$response->successful()) {
                return [];
            }

            $data = $response->json();
            $versions = [];

            foreach ($data as $majorVersion => $versionInfo) {
                if (isset($versionInfo['version'])) {
                    $version = $versionInfo['version'];
                    
                    // 检查是否为博物馆版本（已废弃）
                    $isMuseum = isset($versionInfo['museum']) && $versionInfo['museum'];
                    
                    // 只添加非博物馆版本
                    if (!$isMuseum) {
                        $versions[] = $version;
                    }
                }
            }

            // 排序版本
            usort($versions, 'version_compare');
            
            return array_unique($versions);

        } catch (\Exception $e) {
            Log::error('从官方API获取PHP版本失败', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * 获取PECL扩展版本
     *
     * @param string $extensionName
     * @return array
     */
    protected function getPeclExtensionVersions(string $extensionName): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'PVM-Mirror/1.0'])
                ->get("https://pecl.php.net/rest/r/{$extensionName}/allreleases.xml");

            if (!$response->successful()) {
                return [];
            }

            $xml = simplexml_load_string($response->body());
            if ($xml === false) {
                return [];
            }

            $versions = [];

            // 遍历所有release
            foreach ($xml->r as $release) {
                $version = (string)$release->v;
                $state = (string)$release->s;

                // 只获取稳定版本
                if ($state === 'stable') {
                    $versions[] = $version;
                }
            }

            // 排序版本
            usort($versions, 'version_compare');

            return array_unique($versions);

        } catch (\Exception $e) {
            Log::error("获取PECL扩展版本失败", [
                'extension' => $extensionName,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * 获取GitHub仓库版本
     *
     * @param string $repository
     * @return array
     */
    protected function getGithubRepositoryVersions(string $repository): array
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => 'PVM-Mirror/1.0'])
                ->get("https://api.github.com/repos/{$repository}/tags", [
                    'per_page' => 50
                ]);

            if (!$response->successful()) {
                return [];
            }

            $tags = $response->json();
            $versions = [];

            foreach ($tags as $tag) {
                $tagName = $tag['name'];

                // 匹配版本格式：v1.2.3 或 1.2.3
                if (preg_match('/^v?(\d+\.\d+\.\d+)$/', $tagName, $matches)) {
                    $versions[] = $matches[1];
                }
            }

            // 排序版本
            usort($versions, 'version_compare');

            return array_unique($versions);

        } catch (\Exception $e) {
            Log::error("获取GitHub仓库版本失败", [
                'repository' => $repository,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * 获取GitHub扩展配置
     *
     * @param string $extensionName
     * @return array
     */
    protected function getGithubExtensionConfig(string $extensionName): array
    {
        // 从配置中获取GitHub扩展的仓库映射
        $extensionConfigs = $this->configService->get('mirror.extensions.github_repositories', []);

        return $extensionConfigs[$extensionName] ?? [];
    }

    /**
     * 显示发现结果
     *
     * @param array $results
     */
    protected function displayResults(array $results): void
    {
        $this->newLine();
        $this->info('=== 版本发现结果 ===');
        $this->newLine();

        if (isset($results['php']) && !empty($results['php'])) {
            $this->info('PHP 版本:');
            foreach ($results['php'] as $version) {
                $this->line("  - {$version}");
            }
            $this->info("  总计: " . count($results['php']) . " 个版本");
            $this->newLine();
        }

        if (isset($results['pecl']) && !empty($results['pecl'])) {
            $this->info('PECL 扩展版本:');
            foreach ($results['pecl'] as $extension => $versions) {
                $this->line("  {$extension}:");
                foreach ($versions as $version) {
                    $this->line("    - {$version}");
                }
                $this->info("    总计: " . count($versions) . " 个版本");
                $this->newLine();
            }
        }

        if (isset($results['github']) && !empty($results['github'])) {
            $this->info('GitHub 扩展版本:');
            foreach ($results['github'] as $extension => $versions) {
                $this->line("  {$extension}:");
                foreach ($versions as $version) {
                    $this->line("    - {$version}");
                }
                $this->info("    总计: " . count($versions) . " 个版本");
                $this->newLine();
            }
        }

        if (empty($results) || (empty($results['php'] ?? []) && empty($results['pecl'] ?? []) && empty($results['github'] ?? []))) {
            $this->warn('未发现任何版本信息');
        } else {
            $this->info('版本发现完成');
            $this->line('使用 --save 选项可将发现的版本保存到配置中');
        }
    }

    /**
     * 保存发现的版本到硬编码配置文件
     *
     * @param array $results
     */
    protected function saveDiscoveredVersions(array $results): void
    {
        try {
            $this->info('正在保存发现的版本到硬编码配置文件...');

            $saved = false;

            if (isset($results['php']) && !empty($results['php'])) {
                // 保存PHP版本到硬编码配置文件
                $this->savePhpVersionsToConfig($results['php']);
                $saved = true;
                $this->info('  PHP版本已保存到配置文件');
            }

            if (isset($results['pecl']) && !empty($results['pecl'])) {
                // 保存PECL扩展版本到硬编码配置文件
                $this->savePeclVersionsToConfig($results['pecl']);
                $saved = true;
                $this->info('  PECL扩展版本已保存到配置文件');
            }

            if (isset($results['github']) && !empty($results['github'])) {
                // 保存GitHub扩展版本到硬编码配置文件
                $this->saveGithubVersionsToConfig($results['github']);
                $saved = true;
                $this->info('  GitHub扩展版本已保存到配置文件');
            }

            if ($saved) {
                $this->info('版本信息保存到硬编码配置文件完成');
            } else {
                $this->warn('没有版本信息需要保存');
            }

        } catch (\Exception $e) {
            $this->error('保存版本信息失败: ' . $e->getMessage());
            Log::error('保存发现的版本失败', [
                'error' => $e->getMessage(),
                'results' => $results
            ]);
        }
    }

    /**
     * 显示使用说明
     */
    protected function showUsage(): void
    {
        $this->line('可用类型: php, pecl, github/ext, all');
        $this->newLine();
        $this->line('使用示例:');
        $this->line('  php artisan mirror:discover              # 发现所有版本');
        $this->line('  php artisan mirror:discover php          # 发现PHP版本');
        $this->line('  php artisan mirror:discover pecl         # 发现所有PECL扩展版本');
        $this->line('  php artisan mirror:discover pecl redis   # 发现指定PECL扩展版本');
        $this->line('  php artisan mirror:discover github       # 发现所有GitHub扩展版本');
        $this->line('  php artisan mirror:discover ext swoole   # 发现指定GitHub扩展版本');
        $this->line('  php artisan mirror:discover --json       # JSON格式输出');
        $this->line('  php artisan mirror:discover --save       # 保存发现的版本');
    }

    /**
     * 保存PHP版本到硬编码配置文件
     *
     * @param array $versions
     */
    protected function savePhpVersionsToConfig(array $versions): void
    {
        $configPath = config_path('mirror/php/versions.php');

        // 读取现有配置
        $existingConfig = require $configPath;

        // 按主版本分组新版本
        $groupedVersions = [];
        foreach ($versions as $version) {
            if (preg_match('/^(\d+\.\d+)\./', $version, $matches)) {
                $majorVersion = $matches[1];
                if (!isset($groupedVersions[$majorVersion])) {
                    $groupedVersions[$majorVersion] = [];
                }
                $groupedVersions[$majorVersion][] = $version;
            }
        }

        // 合并到现有配置
        foreach ($groupedVersions as $majorVersion => $newVersions) {
            if (isset($existingConfig[$majorVersion])) {
                // 合并版本列表并去重
                $existingVersions = $existingConfig[$majorVersion]['versions'] ?? [];
                $mergedVersions = array_unique(array_merge($existingVersions, $newVersions));
                usort($mergedVersions, 'version_compare');
                $existingConfig[$majorVersion]['versions'] = $mergedVersions;
            } else {
                // 创建新的主版本配置
                $existingConfig[$majorVersion] = [
                    'major_version' => $majorVersion,
                    'versions' => $newVersions,
                    'metadata' => [
                        'eol' => false,
                        'security_support' => true,
                    ],
                ];
            }
        }

        // 更新元数据
        $existingConfig['metadata']['last_updated'] = date('Y-m-d H:i:s');
        $existingConfig['metadata']['auto_updated'] = true;

        // 写入配置文件
        $this->writeConfigFile($configPath, $existingConfig, 'PHP版本配置文件');
    }

    /**
     * 保存PECL扩展版本到硬编码配置文件
     *
     * @param array $extensions
     */
    protected function savePeclVersionsToConfig(array $extensions): void
    {
        foreach ($extensions as $extensionName => $versions) {
            $configPath = config_path("mirror/pecl/{$extensionName}.php");

            $config = [
                'extension' => $extensionName,
                'name' => ucfirst($extensionName),
                'description' => "PHP {$extensionName} extension",
                'category' => 'extension',
                'versions' => $versions,
                'filter' => [
                    'exclude_patterns' => [
                        '/alpha/',
                        '/beta/',
                        '/RC/',
                    ],
                ],
                'metadata' => [
                    'total_versions' => count($versions),
                    'last_updated' => date('Y-m-d H:i:s'),
                    'discovery_source' => "https://pecl.php.net/package/{$extensionName}",
                    'auto_updated' => true,
                ],
            ];

            $this->writeConfigFile($configPath, $config, "PECL {$extensionName} 扩展版本配置文件");
        }
    }

    /**
     * 保存GitHub扩展版本到硬编码配置文件
     *
     * @param array $extensions
     */
    protected function saveGithubVersionsToConfig(array $extensions): void
    {
        foreach ($extensions as $extensionName => $versions) {
            $configPath = config_path("mirror/extensions/{$extensionName}.php");

            $config = [
                'extension' => $extensionName,
                'name' => ucfirst($extensionName),
                'description' => "GitHub {$extensionName} extension",
                'category' => 'extension',
                'github' => [
                    'owner' => 'unknown',
                    'repo' => $extensionName,
                    'url' => "https://github.com/unknown/{$extensionName}",
                ],
                'versions' => $versions,
                'filter' => [
                    'exclude_patterns' => [
                        '/alpha/',
                        '/beta/',
                        '/RC/',
                    ],
                ],
                'metadata' => [
                    'total_versions' => count($versions),
                    'last_updated' => date('Y-m-d H:i:s'),
                    'discovery_source' => "https://api.github.com/repos/unknown/{$extensionName}/releases",
                    'auto_updated' => true,
                ],
            ];

            $this->writeConfigFile($configPath, $config, "GitHub {$extensionName} 扩展版本配置文件");
        }
    }

    /**
     * 写入配置文件
     *
     * @param string $path
     * @param array $config
     * @param string $description
     */
    protected function writeConfigFile(string $path, array $config, string $description): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $content = "<?php\n\n/**\n * {$description}\n * \n * 此文件由版本发现功能自动更新\n * 最后更新时间: " . date('Y-m-d H:i:s') . "\n */\n\nreturn " . var_export($config, true) . ";\n";

        file_put_contents($path, $content);
    }
}
