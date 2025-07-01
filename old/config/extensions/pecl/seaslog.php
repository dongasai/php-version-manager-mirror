<?php

/**
 * PECL SeasLog 扩展版本配置文件
 */

return [
    'name' => 'seaslog',
    'type' => 'pecl',
    'description' => 'An effective, fast, stable log extension for PHP',
    'version_range' => ['2.2.0', '2.2.5'],
    'all_versions' => ['2.2.0', '2.2.3', '2.2.5'],
    'recommended_versions' => ['2.2.3', '2.2.5'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 3,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/seaslog/allreleases.xml',
        'auto_updated' => false,
    ],
];
