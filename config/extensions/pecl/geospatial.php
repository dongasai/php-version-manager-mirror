<?php

/**
 * PECL geospatial 扩展版本配置文件
 */

return [
    'name' => 'geospatial',
    'type' => 'pecl',
    'description' => 'Geospatial extension',
    'version_range' => ['0.3.2', '0.3.2'],
    'all_versions' => ['0.3.2'],
    'recommended_versions' => ['0.3.2'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/geospatial/allreleases.xml',
        'auto_updated' => false,
    ],
];