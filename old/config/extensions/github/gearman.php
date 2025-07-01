<?php

/**
 * GitHub gearman 扩展版本配置文件
 */

return [
    'name' => 'gearman',
    'type' => 'github',
    'description' => 'Gearman job server',
    'repository' => 'php/pecl-networking-gearman',
    'source' => 'https://github.com/php/pecl-networking-gearman/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
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
        'discovery_source' => 'https://api.github.com/repos/php/pecl-networking-gearman/tags',
        'auto_updated' => false,
    ],
];