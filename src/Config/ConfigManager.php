<?php

namespace Mirror\Config;

/**
 * 配置管理类
 *
 * 用于管理镜像应用的所有配置
 */
class ConfigManager
{
    /**
     * 镜像内容配置
     *
     * @var array
     */
    private $mirrorConfig;

    /**
     * 运行时配置
     *
     * @var array
     */
    private $runtimeConfig;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->loadConfigs();
    }

    /**
     * 加载所有配置
     */
    private function loadConfigs()
    {
        $this->loadMirrorConfig();
        $this->loadRuntimeConfig();
    }

    /**
     * 加载镜像内容配置
     */
    private function loadMirrorConfig()
    {
        $configFile = ROOT_DIR . '/config/mirror.php';

        if (!file_exists($configFile)) {
            throw new \Exception("镜像配置文件不存在: $configFile");
        }

        $this->mirrorConfig = require $configFile;
    }

    /**
     * 加载运行时配置
     */
    private function loadRuntimeConfig()
    {
        $configFile = ROOT_DIR . '/config/runtime.php';

        if (!file_exists($configFile)) {
            throw new \Exception("运行时配置文件不存在: $configFile");
        }

        $this->runtimeConfig = require $configFile;

        // 从环境变量覆盖配置
        $this->loadEnvironmentConfig();
    }

    /**
     * 从环境变量加载配置
     */
    private function loadEnvironmentConfig()
    {
        // 数据目录
        if ($dataDir = getenv('PVM_MIRROR_DATA_DIR')) {
            $this->runtimeConfig['data_dir'] = $dataDir;
        }

        // 日志目录
        if ($logDir = getenv('PVM_MIRROR_LOG_DIR')) {
            $this->runtimeConfig['log_dir'] = $logDir;
        }

        // 日志级别
        if ($logLevel = getenv('PVM_MIRROR_LOG_LEVEL')) {
            $this->runtimeConfig['log_level'] = $logLevel;
        }

        // 服务器配置
        if (!isset($this->runtimeConfig['server'])) {
            $this->runtimeConfig['server'] = [];
        }

        if ($host = getenv('PVM_MIRROR_HOST')) {
            $this->runtimeConfig['server']['host'] = $host;
        }

        if ($port = getenv('PVM_MIRROR_PORT')) {
            $this->runtimeConfig['server']['port'] = (int)$port;
        }

        if ($publicUrl = getenv('PVM_MIRROR_PUBLIC_URL')) {
            $this->runtimeConfig['server']['public_url'] = $publicUrl;
        }

        if ($maxConnections = getenv('PVM_MIRROR_MAX_CONNECTIONS')) {
            $this->runtimeConfig['server']['max_connections'] = (int)$maxConnections;
        }

        if ($timeout = getenv('PVM_MIRROR_TIMEOUT')) {
            $this->runtimeConfig['server']['timeout'] = (int)$timeout;
        }

        // HTTPS配置
        if ($enableHttps = getenv('PVM_MIRROR_ENABLE_HTTPS')) {
            $this->runtimeConfig['server']['enable_https'] = filter_var($enableHttps, FILTER_VALIDATE_BOOLEAN);
        }

        if ($sslCert = getenv('PVM_MIRROR_SSL_CERT')) {
            $this->runtimeConfig['server']['ssl_cert'] = $sslCert;
        }

        if ($sslKey = getenv('PVM_MIRROR_SSL_KEY')) {
            $this->runtimeConfig['server']['ssl_key'] = $sslKey;
        }

        // 缓存配置
        if ($cacheDir = getenv('PVM_MIRROR_CACHE_DIR')) {
            $this->runtimeConfig['cache_dir'] = $cacheDir;
        }

        if (!isset($this->runtimeConfig['cache'])) {
            $this->runtimeConfig['cache'] = [];
        }

        if ($cacheSize = getenv('PVM_MIRROR_CACHE_SIZE')) {
            $this->runtimeConfig['cache']['max_size'] = $cacheSize;
        }

        if ($cacheTtl = getenv('PVM_MIRROR_CACHE_TTL')) {
            $this->runtimeConfig['cache']['ttl'] = (int)$cacheTtl;
        }

        // 同步配置
        if (!isset($this->runtimeConfig['sync'])) {
            $this->runtimeConfig['sync'] = [];
        }

        if ($syncInterval = getenv('PVM_MIRROR_SYNC_INTERVAL')) {
            $this->runtimeConfig['sync']['interval'] = (int)$syncInterval;
        }

        if ($maxRetries = getenv('PVM_MIRROR_MAX_RETRIES')) {
            $this->runtimeConfig['sync']['max_retries'] = (int)$maxRetries;
        }

        if ($retryInterval = getenv('PVM_MIRROR_RETRY_INTERVAL')) {
            $this->runtimeConfig['sync']['retry_interval'] = (int)$retryInterval;
        }

        // 运行环境
        if ($env = getenv('PVM_MIRROR_ENV')) {
            $this->runtimeConfig['environment'] = $env;
        }

        // 调试模式
        if ($debug = getenv('PVM_MIRROR_DEBUG')) {
            $this->runtimeConfig['debug'] = filter_var($debug, FILTER_VALIDATE_BOOLEAN);
        }
    }

    /**
     * 获取数据目录
     *
     * @return string
     */
    public function getDataDir()
    {
        $dataDir = $this->runtimeConfig['data_dir'] ?? '';

        if (empty($dataDir)) {
            $dataDir = ROOT_DIR . '/data';
        }

        return $dataDir;
    }

    /**
     * 获取日志目录
     *
     * @return string
     */
    public function getLogDir()
    {
        $logDir = $this->runtimeConfig['log_dir'] ?? '';

        if (empty($logDir)) {
            $logDir = ROOT_DIR . '/logs';
        }

        return $logDir;
    }

    /**
     * 获取缓存目录
     *
     * @return string
     */
    public function getCacheDir()
    {
        $cacheDir = $this->runtimeConfig['cache_dir'] ?? '';

        if (empty($cacheDir)) {
            $cacheDir = ROOT_DIR . '/cache';
        }

        return $cacheDir;
    }

    /**
     * 获取日志级别
     *
     * @return string
     */
    public function getLogLevel()
    {
        return $this->runtimeConfig['log_level'] ?? 'info';
    }

    /**
     * 获取服务器配置
     *
     * @return array
     */
    public function getServerConfig()
    {
        return $this->runtimeConfig['server'] ?? [
            'host' => '0.0.0.0',
            'port' => 8080,
            'public_url' => 'http://localhost:8080',
        ];
    }

    /**
     * 获取同步配置
     *
     * @return array
     */
    public function getSyncConfig()
    {
        return $this->runtimeConfig['sync'] ?? [
            'interval' => 24,
            'max_retries' => 3,
            'retry_interval' => 300,
        ];
    }

    /**
     * 获取清理配置
     *
     * @return array
     */
    public function getCleanupConfig()
    {
        return $this->runtimeConfig['cleanup'] ?? [
            'keep_versions' => 5,
            'min_age' => 30,
        ];
    }

    /**
     * 获取缓存配置
     *
     * @return array
     */
    public function getCacheConfig()
    {
        return $this->runtimeConfig['cache'] ?? [
            'enable_cache' => true,
            'default_ttl' => 3600,
            'max_size' => 100 * 1024 * 1024, // 100MB
            'clean_interval' => 86400, // 24小时
            'cache_tags' => [
                'php' => true,
                'pecl' => true,
                'extensions' => true,
                'composer' => true,
                'status' => true,
            ],
        ];
    }

    /**
     * 获取资源配置
     *
     * @return array
     */
    public function getResourceConfig()
    {
        return $this->runtimeConfig['resource'] ?? [
            'enable_resource_limits' => true,
            'max_concurrent_downloads' => 10,
            'max_requests_per_minute' => 60,
            'download_speed_limit' => 1024 * 1024, // 1MB/s
            'high_load_threshold' => 80, // CPU使用率超过80%时触发高负载模式
            'high_memory_threshold' => 80, // 内存使用率超过80%时触发高负载模式
            'high_disk_threshold' => 90, // 磁盘使用率超过90%时触发高负载模式
        ];
    }

    /**
     * 获取日志配置
     *
     * @return array
     */
    public function getLogConfig()
    {
        return $this->runtimeConfig['log'] ?? [
            'enable_logging' => true,
            'log_level' => 'info', // debug, info, warning, error, critical
            'log_rotation' => true,
            'max_log_size' => 10 * 1024 * 1024, // 10MB
            'max_log_files' => 10,
            'log_types' => [
                'system' => true,
                'access' => true,
                'error' => true,
                'sync' => true,
                'download' => true,
            ],
        ];
    }

    /**
     * 获取安全配置
     *
     * @return array
     */
    public function getSecurityConfig()
    {
        return $this->runtimeConfig['security'] ?? [
            'enable_access_control' => false,
            'allowed_ips' => [],
        ];
    }

    /**
     * 获取PHP源码配置
     *
     * @return array
     */
    public function getPhpConfig()
    {
        return $this->mirrorConfig['php'] ?? [];
    }

    /**
     * 获取PECL扩展配置
     *
     * @return array
     */
    public function getPeclConfig()
    {
        return $this->mirrorConfig['pecl'] ?? [];
    }

    /**
     * 获取特定扩展配置
     *
     * @return array
     */
    public function getExtensionsConfig()
    {
        return $this->mirrorConfig['extensions'] ?? [];
    }

    /**
     * 获取Composer配置
     *
     * @return array
     */
    public function getComposerConfig()
    {
        return $this->mirrorConfig['composer'] ?? [];
    }

    /**
     * 获取完整的镜像配置
     *
     * @return array
     */
    public function getMirrorConfig()
    {
        return $this->mirrorConfig;
    }

    /**
     * 获取完整的运行时配置
     *
     * @return array
     */
    public function getRuntimeConfig()
    {
        return $this->runtimeConfig;
    }

    /**
     * 获取所有配置
     *
     * @return array
     */
    public function getAllConfig()
    {
        return [
            'mirror' => $this->mirrorConfig,
            'runtime' => $this->runtimeConfig,
        ];
    }

    /**
     * 保存镜像配置
     *
     * @param array $config 配置数组
     * @return bool
     */
    public function saveMirrorConfig(array $config)
    {
        $this->mirrorConfig = $config;

        $content = "<?php\n\n/**\n * PVM 镜像内容配置文件\n * \n * 用于配置需要镜像的内容，包括PHP版本、扩展等\n */\n\nreturn " . var_export($config, true) . ";\n";

        $configFile = ROOT_DIR . '/configMirror/mirror.php';

        return file_put_contents($configFile, $content) !== false;
    }

    /**
     * 保存运行时配置
     *
     * @param array $config 配置数组
     * @return bool
     */
    public function saveRuntimeConfig(array $config)
    {
        $this->runtimeConfig = $config;

        $content = "<?php\n\n/**\n * PVM 镜像运行时配置文件\n * \n * 用于配置镜像服务的运行环境和行为\n */\n\nreturn " . var_export($config, true) . ";\n";

        $configFile = ROOT_DIR . '/configMirror/runtime.php';

        return file_put_contents($configFile, $content) !== false;
    }
}
