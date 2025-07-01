<?php

/**
 * GitHub zstd 扩展版本配置文件
 */

return [
    'name' => 'zstd',
    'type' => 'github',
    'description' => 'Zstandard compression',
    'repository' => 'kjdev/php-ext-zstd',
    'source' => 'https://github.com/kjdev/php-ext-zstd/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['0.13.3'],
    'recommended_versions' => ['0.13.3'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/kjdev/php-ext-zstd/tags',
        'auto_updated' => false,
    ],
];