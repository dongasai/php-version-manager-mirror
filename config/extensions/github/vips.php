<?php

/**
 * GitHub vips 扩展版本配置文件
 */

return [
    'name' => 'vips',
    'type' => 'github',
    'description' => 'VIPS image processing',
    'repository' => 'libvips/php-vips-ext',
    'source' => 'https://github.com/libvips/php-vips-ext/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['1.0.13'],
    'recommended_versions' => ['1.0.13'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/libvips/php-vips-ext/tags',
        'auto_updated' => false,
    ],
];