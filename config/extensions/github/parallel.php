<?php

/**
 * GitHub parallel 扩展版本配置文件
 */

return [
    'name' => 'parallel',
    'type' => 'github',
    'description' => 'Parallel concurrency API',
    'repository' => 'krakjoe/parallel',
    'source' => 'https://github.com/krakjoe/parallel/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v1.2.1'],
    'recommended_versions' => ['v1.2.1'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/krakjoe/parallel/tags',
        'auto_updated' => false,
    ],
];