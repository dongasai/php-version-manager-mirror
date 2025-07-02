<?php

/**
 * GitHub relay 扩展版本配置文件
 */

return [
    'name' => 'relay',
    'type' => 'github',
    'description' => 'Next-generation Redis extension for PHP',
    'repository' => 'cachewerk/relay',
    'source' => 'https://github.com/cachewerk/relay/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v0.6.7', 'v0.8.0'],
    'recommended_versions' => ['v0.6.7', 'v0.8.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 2,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/cachewerk/relay/tags',
        'auto_updated' => false,
    ],
];