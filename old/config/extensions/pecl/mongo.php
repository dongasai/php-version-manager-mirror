<?php

/**
 * PECL mongo 扩展版本配置文件
 */

return [
    'name' => 'mongo',
    'type' => 'pecl',
    'description' => 'MongoDB driver (legacy)',
    'version_range' => ['1.6.16', '1.6.16'],
    'all_versions' => ['1.6.16'],
    'recommended_versions' => ['1.6.16'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/mongo/allreleases.xml',
        'auto_updated' => false,
    ],
];