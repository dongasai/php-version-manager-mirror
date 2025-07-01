<?php

/**
 * GitHub lz4 扩展版本配置文件
 */

return [
    'name' => 'lz4',
    'type' => 'github',
    'description' => 'LZ4 compression',
    'repository' => 'kjdev/php-ext-lz4',
    'source' => 'https://github.com/kjdev/php-ext-lz4/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['0.4.3'],
    'recommended_versions' => ['0.4.3'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/kjdev/php-ext-lz4/tags',
        'auto_updated' => false,
    ],
];