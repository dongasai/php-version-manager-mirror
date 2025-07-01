<?php

/**
 * PECL Sodium 扩展版本配置文件
 */

return [
    'name' => 'sodium',
    'type' => 'pecl',
    'description' => 'Sodium cryptographic library',
    'version_range' => ['2.0.11', '2.1.0'],
    'all_versions' => ['2.0.11', '2.0.23', '2.1.0'],
    'recommended_versions' => ['2.0.23', '2.1.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 3,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/libsodium/allreleases.xml',
        'auto_updated' => false,
        'note' => 'Sodium is built-in since PHP 7.2, PECL version for older PHP versions',
    ],
];
