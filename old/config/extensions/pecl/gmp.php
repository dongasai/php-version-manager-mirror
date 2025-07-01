<?php

/**
 * PECL GMP 扩展版本配置文件
 */

return [
    'name' => 'gmp',
    'type' => 'pecl',
    'description' => 'GNU Multiple Precision',
    'version_range' => ['2.0.0', '2.0.0'],
    'all_versions' => ['2.0.0'],
    'recommended_versions' => ['2.0.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/gmp/allreleases.xml',
        'auto_updated' => false,
        'note' => 'GMP is usually built-in, PECL version for special cases',
    ],
];
