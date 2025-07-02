<?php

/**
 * PECL apcu_bc 扩展版本配置文件
 */

return [
    'name' => 'apcu_bc',
    'type' => 'pecl',
    'description' => 'APCu Backwards Compatibility Module',
    'version_range' => ['1.0.5', '1.0.5'],
    'all_versions' => ['1.0.5'],
    'recommended_versions' => ['1.0.5'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/apcu_bc/allreleases.xml',
        'auto_updated' => false,
    ],
];