<?php

/**
 * GitHub php-ds 扩展版本配置文件
 */

return [
    'name' => 'php-ds',
    'type' => 'github',
    'description' => 'An extension providing efficient data structures for PHP 7',
    'repository' => 'php-ds/ext-ds',
    'source' => 'https://github.com/php-ds/ext-ds/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v1.4.0', 'v1.4.1', 'v1.5.0'],
    'recommended_versions' => ['v1.4.1', 'v1.5.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 3,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/php-ds/ext-ds/tags',
        'auto_updated' => false,
    ],
];
