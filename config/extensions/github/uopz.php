<?php

/**
 * GitHub uopz 扩展版本配置文件
 */

return [
    'name' => 'uopz',
    'type' => 'github',
    'description' => 'User Operations for Zend',
    'repository' => 'krakjoe/uopz',
    'source' => 'https://github.com/krakjoe/uopz/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v7.1.1'],
    'recommended_versions' => ['v7.1.1'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/krakjoe/uopz/tags',
        'auto_updated' => false,
    ],
];