<?php

/**
 * PECL stomp 扩展版本配置文件
 */

return [
    'name' => 'stomp',
    'type' => 'pecl',
    'description' => 'Stomp Client',
    'version_range' => ['2.0.3', '2.0.3'],
    'all_versions' => ['2.0.3'],
    'recommended_versions' => ['2.0.3'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/stomp/allreleases.xml',
        'auto_updated' => false,
    ],
];