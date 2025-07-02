<?php

/**
 * PECL pdo_pgsql 扩展版本配置文件
 * 注意：pdo_pgsql 通常是 PHP 内置扩展，PECL 版本用于特殊需求
 */

return [
    'name' => 'pdo_pgsql',
    'type' => 'pecl',
    'description' => 'PDO driver for PostgreSQL',
    'version_range' => ['8.0.0', '8.4.0'],
    'all_versions' => ['8.0.0', '8.4.0'],
    'recommended_versions' => ['8.0.0', '8.4.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 2,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/pdo_pgsql/allreleases.xml',
        'auto_updated' => false,
        'note' => 'pdo_pgsql is usually built-in, PECL version for special cases',
    ],
];