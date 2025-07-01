<?php

/**
 * GitHub pthreads 扩展版本配置文件
 */

return [
    'name' => 'pthreads',
    'type' => 'github',
    'description' => 'Threading for PHP',
    'repository' => 'krakjoe/pthreads',
    'source' => 'https://github.com/krakjoe/pthreads/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v3.2.0'],
    'recommended_versions' => ['v3.2.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/krakjoe/pthreads/tags',
        'auto_updated' => false,
    ],
];