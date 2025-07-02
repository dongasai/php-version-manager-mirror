<?php

/**
 * GitHub spx 扩展版本配置文件
 */

return [
    'name' => 'spx',
    'type' => 'github',
    'description' => 'Simple profiling extension',
    'repository' => 'NoiseByNorthwest/php-spx',
    'source' => 'https://github.com/NoiseByNorthwest/php-spx/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v0.4.15'],
    'recommended_versions' => ['v0.4.15'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/NoiseByNorthwest/php-spx/tags',
        'auto_updated' => false,
    ],
];