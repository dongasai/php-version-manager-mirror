<?php

/**
 * PECL Ev 扩展版本配置文件
 */

return [
    'name' => 'ev',
    'type' => 'pecl',
    'description' => 'Provides interface to libev library',
    'version_range' => ['1.0.3', '1.2.0'],
    'all_versions' => ['1.0.3', '1.1.5', '1.2.0'],
    'recommended_versions' => ['1.1.5', '1.2.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 3,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/ev/allreleases.xml',
        'auto_updated' => false,
    ],
];
