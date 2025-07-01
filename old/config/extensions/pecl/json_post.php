<?php

/**
 * PECL json_post 扩展版本配置文件
 */

return [
    'name' => 'json_post',
    'type' => 'pecl',
    'description' => 'JSON POST handler',
    'version_range' => ['1.1.0', '1.1.0'],
    'all_versions' => ['1.1.0'],
    'recommended_versions' => ['1.1.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/json_post/allreleases.xml',
        'auto_updated' => false,
    ],
];