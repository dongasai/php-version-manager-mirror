<?php

/**
 * memcached 版本配置文件
 * 
 * 此文件由版本发现服务自动更新
 * 最后更新时间: 2025-05-29 00:04:15
 */

return [
    'name' => 'memcached',
    'type' => 'github',
    'description' => 'PHP extension for interfacing with memcached via libmemcached library',
    'repository' => 'php-memcached-dev/php-memcached',
    'source' => 'https://github.com/php-memcached-dev/php-memcached/archive/refs/tags',
    'pattern' => 'v{version}.tar.gz',
    'all_versions' => [
        '0.1.0',
        '0.1.1',
        '0.1.2',
        '0.1.3',
        '0.1.4',
        '0.1.5',
        '0.2.0',
        '1.0.0',
        '1.0.1',
        '1.0.2',
        '2.2.0',
        '3.0.0',
        '3.0.1',
        '3.0.2',
        '3.0.3',
        '3.0.4',
        '3.1.0',
        '3.1.1',
        '3.1.2',
        '3.1.3',
        '3.1.4',
        '3.1.5',
        '3.2.0',
        '3.3.0',
    ],
    'recommended_versions' => [
        '0.1.4',
        '0.1.5',
        '0.2.0',
        '1.0.0',
        '1.0.1',
        '1.0.2',
        '2.2.0',
        '3.1.5',
        '3.2.0',
        '3.3.0',
    ],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => [
            '/alpha/',
            '/beta/',
            '/RC/',
        ],
    ],
    'metadata' => [
        'total_discovered' => 24,
        'total_recommended' => 10,
        'last_updated' => '2025-05-29 00:04:15',
        'discovery_source' => 'https://api.github.com/repos/php-memcached-dev/php-memcached/tags',
        'auto_updated' => true,
    ],
];
