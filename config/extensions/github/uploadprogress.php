<?php

/**
 * GitHub uploadprogress 扩展版本配置文件
 */

return [
    'name' => 'uploadprogress',
    'type' => 'github',
    'description' => 'Upload progress tracking',
    'repository' => 'php/pecl-php-uploadprogress',
    'source' => 'https://github.com/php/pecl-php-uploadprogress/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['2.0.2'],
    'recommended_versions' => ['2.0.2'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/php/pecl-php-uploadprogress/tags',
        'auto_updated' => false,
    ],
];