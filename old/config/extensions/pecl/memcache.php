<?php

/**
 * PECL memcache 扩展版本配置文件
 */

return [
    'name' => 'memcache',
    'type' => 'pecl',
    'description' => 'memcache extension',
    'version_range' => ['8.0', '8.2'],
    'all_versions' => ['8.0', '8.2'],
    'recommended_versions' => ['8.0', '8.2'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 2,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/memcache/allreleases.xml',
        'auto_updated' => false,
    ],
];