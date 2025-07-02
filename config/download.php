<?php

/**
 * PVM 镜像下载验证配置文件
 *
 * 用于配置下载文件的验证规则和选项
 */

return [
    // 全局下载设置
    'global' => [
        'max_retries' => 3,         // 最大重试次数
        'timeout' => 300,           // 默认超时时间 (秒)
        'verify_content' => true,   // 是否验证内容
        'show_progress' => true,    // 是否显示下载进度
        'user_agent' => 'PVM-Mirror/1.0',
    ],

    // PHP 源码包验证设置
    'php' => [
        'min_size' => 1024 * 1024 * 5,  // 最小文件大小 (5MB)
        'max_retries' => 3,
        'timeout' => 600,                // 增加超时时间
        'verify_content' => true,
        'expected_type' => 'tar.gz',
        'validation' => [
            'check_gzip_header' => true,
            'check_tar_structure' => true,
            'check_configure_script' => true,
            'check_directory_structure' => true,
        ],
    ],

    // Composer 包验证设置
    'composer' => [
        'min_size' => 1024 * 100,       // 最小文件大小 (100KB)
        'max_retries' => 3,
        'timeout' => 300,
        'verify_content' => true,
        'expected_type' => 'phar',
        'validation' => [
            'check_phar_format' => true,
            'check_composer_files' => true,
            'check_executable' => false,  // 不检查可执行性，因为可能没有执行权限
        ],
    ],

    // PECL 扩展包验证设置
    'pecl' => [
        'min_size' => 1024 * 10,        // 最小文件大小 (10KB)
        'max_retries' => 3,
        'timeout' => 300,
        'verify_content' => true,
        'expected_type' => 'tgz',
        'validation' => [
            'check_gzip_header' => true,
            'check_tar_structure' => true,
            'check_config_files' => true,
            'check_source_files' => true,
            'check_directory_structure' => true,
        ],
    ],

    // GitHub 扩展包验证设置
    'github_extensions' => [
        'min_size' => 1024 * 50,        // 最小文件大小 (50KB)
        'max_retries' => 3,
        'timeout' => 300,
        'verify_content' => true,
        'expected_type' => 'tar.gz',
        'validation' => [
            'check_gzip_header' => true,
            'check_tar_structure' => true,
            'check_source_files' => true,
            'check_directory_structure' => true,
            'check_config_files' => false,  // 配置文件不是必需的
        ],
    ],

    // 文件类型验证规则
    'file_types' => [
        'tar.gz' => [
            'magic_bytes' => "\x1f\x8b",
            'extensions' => ['tar.gz', 'tgz'],
            'mime_types' => ['application/gzip', 'application/x-gzip'],
        ],
        'phar' => [
            'magic_bytes' => ['<?php', '#!/usr/bin/env php', 'PK'],
            'extensions' => ['phar'],
            'mime_types' => ['application/x-php'],
        ],
        'zip' => [
            'magic_bytes' => 'PK',
            'extensions' => ['zip'],
            'mime_types' => ['application/zip'],
        ],
    ],

    // 错误检测模式
    'error_detection' => [
        'html_error_pages' => true,     // 检测HTML错误页面
        'http_error_codes' => true,     // 检测HTTP错误代码
        'empty_files' => true,          // 检测空文件
        'error_keywords' => [           // 错误关键词
            'not found',
            '404',
            'error',
            'forbidden',
            'access denied',
            'file not found',
            'internal server error',
            '500',
            '503',
        ],
    ],

    // 验证失败处理
    'failure_handling' => [
        'delete_invalid_files' => true,    // 删除无效文件
        'log_failures' => true,            // 记录失败日志
        'retry_on_validation_failure' => true,  // 验证失败时重试
        'strict_mode' => false,            // 严格模式（验证失败时抛出异常）
    ],

    // 性能优化设置
    'performance' => [
        'parallel_downloads' => false,     // 是否启用并行下载
        'max_parallel' => 3,               // 最大并行数
        'chunk_size' => 8192,              // 下载块大小
        'memory_limit' => '256M',          // 内存限制
    ],

    // 缓存设置
    'cache' => [
        'enable_validation_cache' => true,  // 启用验证缓存
        'cache_duration' => 3600,           // 缓存持续时间 (秒)
        'cache_directory' => 'cache/validation',
    ],

    // 日志设置
    'logging' => [
        'log_downloads' => true,
        'log_validations' => true,
        'log_failures' => true,
        'log_level' => 'info',  // debug, info, warning, error
        'log_file' => 'logs/download.log',
    ],
];
