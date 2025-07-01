<?php

/**
 * PECL oci8 扩展版本配置文件
 */

return [
    'name' => 'oci8',
    'type' => 'pecl',
    'description' => 'Oracle Call Interface',
    'version_range' => ['3.0.1', '3.3.0'],
    'all_versions' => ['3.0.1', '3.3.0'],
    'recommended_versions' => ['3.0.1', '3.3.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 2,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/oci8/allreleases.xml',
        'auto_updated' => false,
    ],
];