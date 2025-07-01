<?php

/**
 * GitHub xdiff 扩展版本配置文件
 */

return [
    'name' => 'xdiff',
    'type' => 'github',
    'description' => 'File differences',
    'repository' => 'php/pecl-text-xdiff',
    'source' => 'https://github.com/php/pecl-text-xdiff/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['2.1.1'],
    'recommended_versions' => ['2.1.1'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/php/pecl-text-xdiff/tags',
        'auto_updated' => false,
    ],
];