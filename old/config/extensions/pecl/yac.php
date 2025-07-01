<?php

/**
 * PECL yac 扩展版本配置文件
 */

return [
    'name' => 'yac',
    'type' => 'pecl',
    'description' => 'Yet Another Cache',
    'version_range' => ['2.3.1', '2.3.1'],
    'all_versions' => ['2.3.1'],
    'recommended_versions' => ['2.3.1'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/yac/allreleases.xml',
        'auto_updated' => false,
    ],
];