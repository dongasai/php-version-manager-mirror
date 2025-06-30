<?php

/**
 * PECL XML 扩展版本配置文件
 * 注意：XML 通常是 PHP 内置扩展，PECL 版本用于特殊需求
 */

return [
    'name' => 'xml',
    'type' => 'pecl',
    'description' => 'XML Parser',
    'version_range' => ['7.4.0', '8.4.0'],
    'all_versions' => ['7.4.0', '8.0.0', '8.1.0', '8.2.0', '8.3.0', '8.4.0'],
    'recommended_versions' => ['8.0.0', '8.1.0', '8.2.0', '8.3.0', '8.4.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 6,
        'total_recommended' => 5,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/xml/allreleases.xml',
        'auto_updated' => false,
        'note' => 'XML is usually built-in, PECL version for special cases',
    ],
];
