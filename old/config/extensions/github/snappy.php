<?php

/**
 * GitHub snappy 扩展版本配置文件
 */

return [
    'name' => 'snappy',
    'type' => 'github',
    'description' => 'Snappy compression',
    'repository' => 'kjdev/php-ext-snappy',
    'source' => 'https://github.com/kjdev/php-ext-snappy/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['0.2.1'],
    'recommended_versions' => ['0.2.1'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/kjdev/php-ext-snappy/tags',
        'auto_updated' => false,
    ],
];