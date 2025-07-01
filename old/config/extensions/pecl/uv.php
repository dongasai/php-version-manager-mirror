<?php

/**
 * PECL UV 扩展版本配置文件
 */

return [
    'name' => 'uv',
    'type' => 'pecl',
    'description' => 'Provides interface to libuv library',
    'version_range' => ['0.2.4', '0.3.0'],
    'all_versions' => ['0.2.4', '0.3.0'],
    'recommended_versions' => ['0.2.4', '0.3.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 2,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/uv/allreleases.xml',
        'auto_updated' => false,
    ],
];
