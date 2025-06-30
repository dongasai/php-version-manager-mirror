<?php

/**
 * PECL parle 扩展版本配置文件
 */

return [
    'name' => 'parle',
    'type' => 'pecl',
    'description' => 'Parsing and lexing',
    'version_range' => ['0.8.5', '0.8.5'],
    'all_versions' => ['0.8.5'],
    'recommended_versions' => ['0.8.5'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/parle/allreleases.xml',
        'auto_updated' => false,
    ],
];