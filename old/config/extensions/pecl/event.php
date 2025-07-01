<?php

/**
 * PECL Event 扩展版本配置文件
 */

return [
    'name' => 'event',
    'type' => 'pecl',
    'description' => 'Provides interface to libevent library',
    'version_range' => ['3.0.8', '3.1.4'],
    'all_versions' => ['3.0.8', '3.1.0', '3.1.2', '3.1.4'],
    'recommended_versions' => ['3.1.0', '3.1.2', '3.1.4'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 4,
        'total_recommended' => 3,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/event/allreleases.xml',
        'auto_updated' => false,
    ],
];
