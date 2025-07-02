<?php

/**
 * PECL APCu 扩展版本配置文件
 */

return [
    'name' => 'apcu',
    'type' => 'pecl',
    'description' => 'APCu - APC User Cache',
    'version_range' => ['5.1.20', '5.1.23'],
    'all_versions' => ['5.1.20', '5.1.21', '5.1.22', '5.1.23'],
    'recommended_versions' => ['5.1.20', '5.1.21', '5.1.22', '5.1.23'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 4,
        'total_recommended' => 4,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/apcu/allreleases.xml',
        'auto_updated' => false,
    ],
];
