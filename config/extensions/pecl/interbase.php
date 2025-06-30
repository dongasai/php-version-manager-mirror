<?php

/**
 * PECL interbase 扩展版本配置文件
 */

return [
    'name' => 'interbase',
    'type' => 'pecl',
    'description' => 'InterBase/Firebird functions',
    'version_range' => ['1.0.0', '1.0.0'],
    'all_versions' => ['1.0.0'],
    'recommended_versions' => ['1.0.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/interbase/allreleases.xml',
        'auto_updated' => false,
    ],
];