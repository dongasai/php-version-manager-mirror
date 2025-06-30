<?php

/**
 * PECL luasandbox 扩展版本配置文件
 */

return [
    'name' => 'luasandbox',
    'type' => 'pecl',
    'description' => 'Lua sandbox',
    'version_range' => ['4.1.2', '4.1.2'],
    'all_versions' => ['4.1.2'],
    'recommended_versions' => ['4.1.2'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/luasandbox/allreleases.xml',
        'auto_updated' => false,
    ],
];