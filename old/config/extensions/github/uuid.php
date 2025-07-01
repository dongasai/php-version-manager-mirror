<?php

/**
 * GitHub uuid 扩展版本配置文件
 */

return [
    'name' => 'uuid',
    'type' => 'github',
    'description' => 'UUID functions',
    'repository' => 'php/pecl-networking-uuid',
    'source' => 'https://github.com/php/pecl-networking-uuid/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['1.2.0'],
    'recommended_versions' => ['1.2.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/php/pecl-networking-uuid/tags',
        'auto_updated' => false,
    ],
];