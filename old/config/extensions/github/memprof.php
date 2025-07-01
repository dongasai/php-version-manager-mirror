<?php

/**
 * GitHub memprof 扩展版本配置文件
 */

return [
    'name' => 'memprof',
    'type' => 'github',
    'description' => 'Memory usage profiler',
    'repository' => 'arnaud-lb/php-memory-profiler',
    'source' => 'https://github.com/arnaud-lb/php-memory-profiler/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v3.0.2'],
    'recommended_versions' => ['v3.0.2'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/arnaud-lb/php-memory-profiler/tags',
        'auto_updated' => false,
    ],
];