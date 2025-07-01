<?php

/**
 * PECL Mcrypt 扩展版本配置文件
 */

return [
    'name' => 'mcrypt',
    'type' => 'pecl',
    'description' => 'Mcrypt cryptographic library',
    'version_range' => ['1.0.4', '1.0.7'],
    'all_versions' => ['1.0.4', '1.0.5', '1.0.6', '1.0.7'],
    'recommended_versions' => ['1.0.6', '1.0.7'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 4,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/mcrypt/allreleases.xml',
        'auto_updated' => false,
        'note' => 'Mcrypt was deprecated in PHP 7.1 and removed in PHP 7.2, use Sodium instead',
    ],
];
