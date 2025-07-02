<?php

/**
 * PECL jsmin 扩展版本配置文件
 */

return [
    'name' => 'jsmin',
    'type' => 'pecl',
    'description' => 'JavaScript minifier',
    'version_range' => ['3.0.0', '3.0.0'],
    'all_versions' => ['3.0.0'],
    'recommended_versions' => ['3.0.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/jsmin/allreleases.xml',
        'auto_updated' => false,
    ],
];