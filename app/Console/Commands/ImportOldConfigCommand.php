<?php

namespace App\Console\Commands;

use App\Services\ConfigService;
use Illuminate\Console\Command;

class ImportOldConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mirror:import-old-config
                            {--force : 强制覆盖已存在的配置}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '导入old项目的配置到新系统中';

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
     *
     * @return int
     */
    public function handle()
    {
        $this->info('开始导入old项目配置...');

        $oldConfigPath = base_path('old/config');
        
        if (!is_dir($oldConfigPath)) {
            $this->error('old/config 目录不存在');
            return 1;
        }

        $force = $this->option('force');

        try {
            // 导入运行时配置
            $this->importRuntimeConfig($force);
            
            // 导入镜像配置
            $this->importMirrorConfig($force);
            
            $this->info('配置导入完成！');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('配置导入失败: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * 导入运行时配置
     *
     * @param bool $force
     * @return void
     */
    protected function importRuntimeConfig(bool $force): void
    {
        $runtimeConfigPath = base_path('old/config/runtime.php');
        
        if (!file_exists($runtimeConfigPath)) {
            $this->warn('runtime.php 配置文件不存在，跳过');
            return;
        }

        $this->info('导入运行时配置...');
        
        $oldConfig = require $runtimeConfigPath;
        
        // 映射old配置到新配置
        $configMappings = [
            // 系统配置
            'system.data_dir' => base_path('data'), // 使用新的data目录
            'system.cache_dir' => $oldConfig['cache']['cache_dir'] ?: storage_path('app/mirror-cache'),
            'system.temp_dir' => storage_path('app/temp'),
            
            // 服务器配置
            'server.host' => $oldConfig['server']['host'] ?? '0.0.0.0',
            'server.port' => $oldConfig['server']['port'] ?? 34403,
            'server.public_url' => $oldConfig['server']['public_url'] ?? 'http://localhost:34403',
            'server.max_connections' => $oldConfig['server']['max_connections'] ?? 100,
            'server.timeout' => $oldConfig['server']['timeout'] ?? 30,
            'server.enable_https' => $oldConfig['server']['enable_https'] ?? false,
            
            // 同步配置
            'sync.interval' => $oldConfig['sync']['interval'] ?? 24,
            'sync.max_retries' => $oldConfig['sync']['max_retries'] ?? 3,
            'sync.retry_interval' => $oldConfig['sync']['retry_interval'] ?? 300,
            'sync.download_timeout' => $oldConfig['sync']['download_timeout'] ?? 600,
            'sync.max_parallel_downloads' => $oldConfig['sync']['max_parallel_downloads'] ?? 1,
            'sync.auto_sync_on_start' => $oldConfig['sync']['auto_sync_on_start'] ?? true,
            'sync.use_proxy' => $oldConfig['sync']['use_proxy'] ?? false,
            'sync.proxy' => $oldConfig['sync']['proxy'] ?? '',
            
            // 缓存配置
            'cache.enable' => $oldConfig['cache']['enable_cache'] ?? true,
            'cache.ttl' => $oldConfig['cache']['default_ttl'] ?? 3600,
            'cache.max_size' => $oldConfig['cache']['max_size'] ?? 104857600,
            'cache.clean_interval' => $oldConfig['cache']['clean_interval'] ?? 86400,
            
            // 日志配置
            'log.enable_logging' => $oldConfig['log']['enable_logging'] ?? true,
            'log.log_level' => $oldConfig['log']['log_level'] ?? 'info',
            'log.log_rotation' => $oldConfig['log']['log_rotation'] ?? true,
            'log.max_log_size' => $oldConfig['log']['max_log_size'] ?? 10485760,
            'log.max_log_files' => $oldConfig['log']['max_log_files'] ?? 10,
            
            // 安全配置
            'security.enable_access_control' => $oldConfig['security']['enable_access_control'] ?? false,
            'security.enable_ip_whitelist' => $oldConfig['security']['enable_ip_whitelist'] ?? false,
            'security.allowed_ips' => $oldConfig['security']['allowed_ips'] ?? [],
            'security.enable_basic_auth' => $oldConfig['security']['enable_basic_auth'] ?? false,
            'security.auth_users' => $oldConfig['security']['auth_users'] ?? [],
            'security.verify_integrity' => $oldConfig['security']['verify_integrity'] ?? true,
            
            // 资源限制配置
            'resource.enable_resource_limits' => $oldConfig['resource']['enable_resource_limits'] ?? false,
            'resource.max_concurrent_downloads' => $oldConfig['resource']['max_concurrent_downloads'] ?? 10,
            'resource.max_requests_per_minute' => $oldConfig['resource']['max_requests_per_minute'] ?? 60,
            'resource.download_speed_limit' => $oldConfig['resource']['download_speed_limit'] ?? 0,
            'resource.high_load_threshold' => $oldConfig['resource']['high_load_threshold'] ?? 80,
            'resource.high_memory_threshold' => $oldConfig['resource']['high_memory_threshold'] ?? 80,
            'resource.high_disk_threshold' => $oldConfig['resource']['high_disk_threshold'] ?? 90,
        ];

        foreach ($configMappings as $key => $value) {
            $this->setConfigIfNotExists($key, $value, $force);
        }
    }

    /**
     * 导入镜像配置
     *
     * @param bool $force
     * @return void
     */
    protected function importMirrorConfig(bool $force): void
    {
        $mirrorConfigPath = base_path('old/config/mirror.php');
        
        if (!file_exists($mirrorConfigPath)) {
            $this->warn('mirror.php 配置文件不存在，跳过');
            return;
        }

        $this->info('导入镜像配置...');
        
        $oldConfig = require $mirrorConfigPath;
        
        // 映射镜像配置
        $configMappings = [
            // 版本发现配置
            'mirror.discovery.enabled' => $oldConfig['discovery']['enabled'] ?? true,
            'mirror.discovery.timeout' => $oldConfig['discovery']['timeout'] ?? 30,
            'mirror.discovery.cache_ttl' => $oldConfig['discovery']['cache_ttl'] ?? 3600,
            'mirror.discovery.use_config_fallback' => $oldConfig['discovery']['use_config_fallback'] ?? true,
            'mirror.discovery.stable_only' => $oldConfig['discovery']['stable_only'] ?? true,
            
            // PHP配置
            'mirror.php.enabled' => $oldConfig['php']['enabled'] ?? true,
            'mirror.php.source' => $oldConfig['php']['source'] ?? 'https://www.php.net/distributions',
            'mirror.php.discovery_api' => $oldConfig['php']['discovery_api'] ?? 'https://www.php.net/releases/index.php?json=1',
            'mirror.php.pattern' => $oldConfig['php']['pattern'] ?? 'php-{version}.tar.gz',
            
            // PECL配置
            'mirror.pecl.enabled' => $oldConfig['pecl']['enabled'] ?? true,
            'mirror.pecl.source' => $oldConfig['pecl']['source'] ?? 'https://pecl.php.net/get',
            'mirror.pecl.pattern' => $oldConfig['pecl']['pattern'] ?? '{extension}-{version}.tgz',
            'mirror.pecl.extensions' => $oldConfig['pecl']['extensions'] ?? [],
            
            // GitHub扩展配置
            'mirror.extensions.enabled' => $oldConfig['extensions']['enabled'] ?? true,
            'mirror.extensions.extensions' => $oldConfig['extensions']['extensions'] ?? [],
            
            // Composer配置
            'mirror.composer.enabled' => $oldConfig['composer']['enabled'] ?? true,
            'mirror.composer.source' => $oldConfig['composer']['source'] ?? 'https://getcomposer.org/download',
            'mirror.composer.pattern' => $oldConfig['composer']['pattern'] ?? 'composer-{version}.phar',
            'mirror.composer.url_pattern' => $oldConfig['composer']['url_pattern'] ?? '{source}/{version}/composer.phar',
        ];

        foreach ($configMappings as $key => $value) {
            $this->setConfigIfNotExists($key, $value, $force);
        }
    }

    /**
     * 设置配置（如果不存在或强制覆盖）
     *
     * @param string $key
     * @param mixed $value
     * @param bool $force
     * @return void
     */
    protected function setConfigIfNotExists(string $key, $value, bool $force): void
    {
        $existing = $this->configService->get($key);
        
        if ($existing === null || $force) {
            $description = $this->getConfigDescription($key);
            $this->configService->set($key, $value, $description);
            
            $status = $existing === null ? '新增' : '更新';
            $this->line("  {$status}: {$key}");
        } else {
            $this->line("  跳过: {$key} (已存在)");
        }
    }

    /**
     * 获取配置描述
     *
     * @param string $key
     * @return string
     */
    protected function getConfigDescription(string $key): string
    {
        $descriptions = [
            'system.data_dir' => '镜像数据存储目录',
            'system.cache_dir' => '镜像缓存目录',
            'system.temp_dir' => '临时文件目录',
            'server.host' => '服务器监听地址',
            'server.port' => '服务器监听端口',
            'server.public_url' => '公开访问URL',
            'server.max_connections' => '最大并发连接数',
            'server.timeout' => '请求超时时间（秒）',
            'server.enable_https' => '是否启用HTTPS',
            'sync.interval' => '自动同步间隔（小时）',
            'sync.max_retries' => '最大重试次数',
            'sync.retry_interval' => '重试间隔（秒）',
            'sync.download_timeout' => '下载超时时间（秒）',
            'sync.max_parallel_downloads' => '最大并行下载数',
            'sync.auto_sync_on_start' => '启动时自动同步',
            'sync.use_proxy' => '是否使用代理',
            'sync.proxy' => '代理服务器',
            'cache.enable' => '启用缓存',
            'cache.ttl' => '缓存生存时间（秒）',
            'cache.max_size' => '最大缓存大小（字节）',
            'cache.clean_interval' => '缓存清理间隔（秒）',
            'log.enable_logging' => '启用日志记录',
            'log.log_level' => '日志级别',
            'log.log_rotation' => '启用日志轮转',
            'log.max_log_size' => '最大日志文件大小（字节）',
            'log.max_log_files' => '最大日志文件数量',
            'security.enable_access_control' => '启用访问控制',
            'security.enable_ip_whitelist' => '启用IP白名单',
            'security.allowed_ips' => '允许访问的IP地址列表',
            'security.enable_basic_auth' => '启用基本认证',
            'security.auth_users' => '认证用户列表',
            'security.verify_integrity' => '验证文件完整性',
            'resource.enable_resource_limits' => '启用资源限制',
            'resource.max_concurrent_downloads' => '最大并发下载数',
            'resource.max_requests_per_minute' => '每分钟最大请求数',
            'resource.download_speed_limit' => '下载速度限制（字节/秒）',
            'resource.high_load_threshold' => 'CPU使用率阈值（百分比）',
            'resource.high_memory_threshold' => '内存使用率阈值（百分比）',
            'resource.high_disk_threshold' => '磁盘使用率阈值（百分比）',
            'mirror.discovery.enabled' => '启用自动版本发现',
            'mirror.discovery.timeout' => 'API调用超时时间（秒）',
            'mirror.discovery.cache_ttl' => '缓存时间（秒）',
            'mirror.discovery.use_config_fallback' => '使用配置文件作为补充',
            'mirror.discovery.stable_only' => '只获取稳定版本',
            'mirror.php.enabled' => '启用PHP镜像',
            'mirror.php.source' => 'PHP官方源',
            'mirror.php.discovery_api' => 'PHP版本发现API',
            'mirror.php.pattern' => 'PHP文件名模式',
            'mirror.pecl.enabled' => '启用PECL镜像',
            'mirror.pecl.source' => 'PECL官方源',
            'mirror.pecl.pattern' => 'PECL文件名模式',
            'mirror.pecl.extensions' => 'PECL支持的扩展列表',
            'mirror.extensions.enabled' => '启用GitHub扩展镜像',
            'mirror.extensions.extensions' => 'GitHub支持的扩展列表',
            'mirror.composer.enabled' => '启用Composer镜像',
            'mirror.composer.source' => 'Composer官方源',
            'mirror.composer.pattern' => 'Composer文件名模式',
            'mirror.composer.url_pattern' => 'Composer URL模式',
        ];

        return $descriptions[$key] ?? '配置项';
    }
}
