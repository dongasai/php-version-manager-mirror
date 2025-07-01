<?php

/**
 * PECL oauth 扩展版本配置文件
 */

return [
    'name' => 'oauth',
    'type' => 'pecl',
    'description' => 'OAuth consumer extension',
    'version_range' => ['2.0.7', '2.0.7'],
    'all_versions' => ['2.0.7'],
    'recommended_versions' => ['2.0.7'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/oauth/allreleases.xml',
        'auto_updated' => false,
    ],
];