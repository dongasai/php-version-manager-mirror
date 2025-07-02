<?php

/**
 * GitHub Phalcon 扩展版本配置文件
 */

return [
    'name' => 'phalcon',
    'type' => 'github',
    'description' => 'High performance, full-stack PHP framework',
    'repository' => 'phalcon/cphalcon',
    'source' => 'https://github.com/phalcon/cphalcon/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v4.1.2', 'v5.0.5', 'v5.6.2', 'v5.8.0'],
    'recommended_versions' => ['v5.0.5', 'v5.6.2', 'v5.8.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 4,
        'total_recommended' => 3,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/phalcon/cphalcon/tags',
        'auto_updated' => false,
    ],
];
