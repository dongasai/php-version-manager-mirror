<?php

/**
 * PECL mysql 扩展版本配置文件
 * 注意：mysql 通常是 PHP 内置扩展，PECL 版本用于特殊需求
 */

return [
    'name' => 'mysql',
    'type' => 'pecl',
    'description' => 'MySQL functions (deprecated)',
    'version_range' => ['1.0', '1.0'],
    'all_versions' => ['1.0'],
    'recommended_versions' => ['1.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/mysql/allreleases.xml',
        'auto_updated' => false,
        'note' => 'mysql is usually built-in, PECL version for special cases',
    ],
];