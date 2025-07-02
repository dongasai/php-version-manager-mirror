<?php

/**
 * GitHub simdjson 扩展版本配置文件
 */

return [
    'name' => 'simdjson',
    'type' => 'github',
    'description' => 'Fast JSON parser',
    'repository' => 'crazyxman/simdjson_php',
    'source' => 'https://github.com/crazyxman/simdjson_php/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v2.1.0'],
    'recommended_versions' => ['v2.1.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/crazyxman/simdjson_php/tags',
        'auto_updated' => false,
    ],
];