<?php

/**
 * PECL xsl 扩展版本配置文件
 */

return [
    'name' => 'xsl',
    'type' => 'pecl',
    'description' => 'XSL functions',
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
        'discovery_source' => 'https://pecl.php.net/rest/r/xsl/allreleases.xml',
        'auto_updated' => false,
        'note' => 'xsl is usually built-in, PECL version for special cases',
    ],
];