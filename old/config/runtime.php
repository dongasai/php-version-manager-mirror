<?php

/**
 * PVM 镜像运行时配置文件
 *
 * 用于配置镜像服务的运行环境和行为
 */

return [
    // 自定义数据目录，留空则使用默认目录 ROOT_DIR/data
    'data_dir' => '',

    // 日志目录，留空则使用默认目录 ROOT_DIR/logs
    'log_dir' => '',

    // 日志级别：debug, info, warning, error
    'log_level' => 'info',

    // 镜像服务配置
    'server' => [
        // 监听主机
        'host' => '0.0.0.0',

        // 监听端口
        'port' => 34403,

        // 公开URL，用于生成下载链接
        'public_url' => 'http://localhost:34403',

        // 最大并发连接数
        'max_connections' => 100,

        // 请求超时时间（秒）
        'timeout' => 30,

        // 是否启用HTTPS
        'enable_https' => false,

        // SSL证书路径（如果启用HTTPS）
        'ssl_cert' => '',

        // SSL密钥路径（如果启用HTTPS）
        'ssl_key' => '',
    ],

    // 镜像同步配置
    'sync' => [
        // 同步间隔（小时）
        'interval' => 24,

        // 最大重试次数
        'max_retries' => 3,

        // 重试间隔（秒）
        'retry_interval' => 300,

        // 下载超时时间（秒）
        'download_timeout' => 600,

        // 最大并行下载数
        'max_parallel_downloads' => 1,

        // 是否在启动时自动同步
        'auto_sync_on_start' => true,

        // 是否使用代理
        'use_proxy' => false,

        // 代理服务器
        'proxy' => '',
    ],

    // 镜像清理配置
    'cleanup' => [
        // 每个主版本保留的最新版本数量
        'keep_versions' => 9999,

        // 最小保留天数
        'min_age' => 30,

        // 是否自动清理
        'auto_cleanup' => true,

        // 清理间隔（天）
        'cleanup_interval' => 7,
    ],

    // 缓存配置
    'cache' => [
        // 是否启用缓存
        'enable_cache' => true,

        // 缓存目录，留空则使用默认目录 ROOT_DIR/cache
        'cache_dir' => '',

        // 默认缓存过期时间（秒）
        'default_ttl' => 3600,

        // 最大缓存大小（字节）
        'max_size' => 104857600, // 100MB

        // 缓存清理间隔（秒）
        'clean_interval' => 86400, // 24小时

        // 缓存标签配置
        'cache_tags' => [
            // 是否缓存PHP源码列表
            'php' => true,

            // 是否缓存PECL扩展列表
            'pecl' => true,

            // 是否缓存特定扩展列表
            'extensions' => true,

            // 是否缓存Composer包列表
            'composer' => true,

            // 是否缓存状态信息
            'status' => true,
        ],
    ],

    // 资源限制配置
    'resource' => [
        // 是否启用资源限制
        'enable_resource_limits' => false,

        // 最大并发下载数
        'max_concurrent_downloads' => 10,

        // 每分钟最大请求数（按IP限制）
        'max_requests_per_minute' => 60,

        // 下载速度限制（字节/秒，0表示不限制）
        'download_speed_limit' => 0, // 禁用速度限制

        // CPU使用率阈值（百分比）
        'high_load_threshold' => 80,

        // 内存使用率阈值（百分比）
        'high_memory_threshold' => 80,

        // 磁盘使用率阈值（百分比）
        'high_disk_threshold' => 90,
    ],

    // 安全配置
    'security' => [
        // 是否启用访问控制
        'enable_access_control' => false,

        // 是否启用IP白名单
        'enable_ip_whitelist' => false,

        // 允许的IP地址列表
        'allowed_ips' => [],

        // 是否启用基本认证（用户名/密码）
        'enable_basic_auth' => false,

        // 基本认证用户列表 (格式: ['username' => 'password', ...])
        'auth_users' => [],

        // 是否验证文件完整性
        'verify_integrity' => true,
    ],

    // 日志配置
    'log' => [
        // 是否启用日志
        'enable_logging' => true,

        // 日志级别：debug, info, warning, error, critical
        'log_level' => 'info',

        // 是否启用日志轮转
        'log_rotation' => true,

        // 单个日志文件最大大小（字节）
        'max_log_size' => 10485760, // 10MB

        // 最大日志文件数
        'max_log_files' => 10,

        // 日志类型配置
        'log_types' => [
            // 是否记录系统日志
            'system' => true,

            // 是否记录访问日志
            'access' => true,

            // 是否记录错误日志
            'error' => true,

            // 是否记录同步日志
            'sync' => true,

            // 是否记录下载日志
            'download' => true,
        ],
    ],
];
