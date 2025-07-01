<?php

/**
 * memcached 版本配置文件
 * 
 * 此文件由版本发现服务自动更新
 * 最后更新时间: 2025-05-29 00:04:08
 */

return [
    'name' => 'memcached',
    'type' => 'pecl',
    'description' => 'PHP extension for interfacing with memcached via libmemcached library',
    'version_range' => [
        '3.1.5',
        '3.2.0',
    ],
    'all_versions' => [
        '1.0.0',
        '1.0.1',
        '1.0.2',
        '2.0.0',
        '2.0.1',
        '2.1.0',
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
        '1.0.0',
        '1.0.1',
        '1.0.2',
        '2.0.1',
        '2.1.0',
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
        'total_discovered' => 20,
        'total_recommended' => 9,
        'last_updated' => '2025-05-29 00:04:08',
        'discovery_source' => 'https://pecl.php.net/rest/r/memcached/allreleases.xml',
        'auto_updated' => true,
    ],
];
