<?php

/**
 * GitHub maxminddb 扩展版本配置文件
 */

return [
    'name' => 'maxminddb',
    'type' => 'github',
    'description' => 'MaxMind DB Reader',
    'repository' => 'maxmind/MaxMind-DB-Reader-php',
    'source' => 'https://github.com/maxmind/MaxMind-DB-Reader-php/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v1.11.1'],
    'recommended_versions' => ['v1.11.1'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/maxmind/MaxMind-DB-Reader-php/tags',
        'auto_updated' => false,
    ],
];