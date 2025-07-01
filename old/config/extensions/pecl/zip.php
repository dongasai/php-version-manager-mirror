<?php

/**
 * PECL Zip 扩展版本配置文件
 */

return [
    'name' => 'zip',
    'type' => 'pecl',
    'description' => 'Zip File Functions',
    'version_range' => ['1.19.5', '1.22.3'],
    'all_versions' => ['1.19.5', '1.20.1', '1.21.1', '1.22.3'],
    'recommended_versions' => ['1.20.1', '1.21.1', '1.22.3'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 4,
        'total_recommended' => 3,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/zip/allreleases.xml',
        'auto_updated' => false,
        'note' => 'Zip is usually built-in, PECL version provides additional features',
    ],
];
