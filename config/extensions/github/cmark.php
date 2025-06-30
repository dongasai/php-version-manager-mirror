<?php

/**
 * GitHub cmark 扩展版本配置文件
 */

return [
    'name' => 'cmark',
    'type' => 'github',
    'description' => 'CommonMark parser',
    'repository' => 'krakjoe/cmark',
    'source' => 'https://github.com/krakjoe/cmark/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v1.2.0'],
    'recommended_versions' => ['v1.2.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/krakjoe/cmark/tags',
        'auto_updated' => false,
    ],
];