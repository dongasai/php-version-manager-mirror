<?php

/**
 * PECL Intl 扩展版本配置文件
 */

return [
    'name' => 'intl',
    'type' => 'pecl',
    'description' => 'Internationalization extension',
    'version_range' => ['3.0.0', '3.0.0'],
    'all_versions' => ['3.0.0'],
    'recommended_versions' => ['3.0.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/intl/allreleases.xml',
        'auto_updated' => false,
        'note' => 'Intl is usually built-in since PHP 5.3, PECL version for special cases',
    ],
];
