<?php

/**
 * PECL enchant 扩展版本配置文件
 */

return [
    'name' => 'enchant',
    'type' => 'pecl',
    'description' => 'Enchant spelling library',
    'version_range' => ['8.0.0', '8.4.0'],
    'all_versions' => ['8.0.0', '8.4.0'],
    'recommended_versions' => ['8.0.0', '8.4.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 2,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/enchant/allreleases.xml',
        'auto_updated' => false,
        'note' => 'enchant is usually built-in, PECL version for special cases',
    ],
];