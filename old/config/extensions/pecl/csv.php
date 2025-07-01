<?php

/**
 * PECL csv 扩展版本配置文件
 */

return [
    'name' => 'csv',
    'type' => 'pecl',
    'description' => 'CSV functions',
    'version_range' => ['1.0.2', '1.0.2'],
    'all_versions' => ['1.0.2'],
    'recommended_versions' => ['1.0.2'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/csv/allreleases.xml',
        'auto_updated' => false,
    ],
];