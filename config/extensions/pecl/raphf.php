<?php

/**
 * PECL raphf 扩展版本配置文件
 */

return [
    'name' => 'raphf',
    'type' => 'pecl',
    'description' => 'Resource and persistent handles',
    'version_range' => ['2.0.1', '2.0.1'],
    'all_versions' => ['2.0.1'],
    'recommended_versions' => ['2.0.1'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/raphf/allreleases.xml',
        'auto_updated' => false,
    ],
];