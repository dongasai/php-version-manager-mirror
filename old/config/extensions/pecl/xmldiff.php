<?php

/**
 * PECL xmldiff 扩展版本配置文件
 */

return [
    'name' => 'xmldiff',
    'type' => 'pecl',
    'description' => 'XML diff and merge',
    'version_range' => ['1.1.3', '1.1.3'],
    'all_versions' => ['1.1.3'],
    'recommended_versions' => ['1.1.3'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/xmldiff/allreleases.xml',
        'auto_updated' => false,
    ],
];