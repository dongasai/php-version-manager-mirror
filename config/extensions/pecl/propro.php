<?php

/**
 * PECL propro 扩展版本配置文件
 */

return [
    'name' => 'propro',
    'type' => 'pecl',
    'description' => 'Property proxy',
    'version_range' => ['2.1.0', '2.1.0'],
    'all_versions' => ['2.1.0'],
    'recommended_versions' => ['2.1.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/propro/allreleases.xml',
        'auto_updated' => false,
    ],
];