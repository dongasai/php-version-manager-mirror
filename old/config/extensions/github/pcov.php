<?php

/**
 * GitHub pcov 扩展版本配置文件
 */

return [
    'name' => 'pcov',
    'type' => 'github',
    'description' => 'Code coverage driver',
    'repository' => 'krakjoe/pcov',
    'source' => 'https://github.com/krakjoe/pcov/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v1.0.11'],
    'recommended_versions' => ['v1.0.11'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/krakjoe/pcov/tags',
        'auto_updated' => false,
    ],
];