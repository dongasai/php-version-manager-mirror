<?php

/**
 * PECL decimal 扩展版本配置文件
 */

return [
    'name' => 'decimal',
    'type' => 'pecl',
    'description' => 'Arbitrary precision decimal type',
    'version_range' => ['1.5.0', '1.5.0'],
    'all_versions' => ['1.5.0'],
    'recommended_versions' => ['1.5.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/decimal/allreleases.xml',
        'auto_updated' => false,
    ],
];