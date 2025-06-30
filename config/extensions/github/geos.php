<?php

/**
 * GitHub geos 扩展版本配置文件
 */

return [
    'name' => 'geos',
    'type' => 'github',
    'description' => 'GEOS geometry engine',
    'repository' => 'libgeos/php-geos',
    'source' => 'https://github.com/libgeos/php-geos/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['1.0.0'],
    'recommended_versions' => ['1.0.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/libgeos/php-geos/tags',
        'auto_updated' => false,
    ],
];