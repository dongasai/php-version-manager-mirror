<?php

/**
 * PECL Phalcon 扩展版本配置文件
 */

return [
    'name' => 'phalcon',
    'type' => 'pecl',
    'description' => 'Phalcon Framework',
    'version_range' => ['4.1.2', '5.8.0'],
    'all_versions' => ['4.1.2', '5.0.5', '5.6.2', '5.8.0'],
    'recommended_versions' => ['5.0.5', '5.6.2', '5.8.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 4,
        'total_recommended' => 3,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/phalcon/allreleases.xml',
        'auto_updated' => false,
    ],
];
