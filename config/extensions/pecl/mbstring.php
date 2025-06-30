<?php

/**
 * PECL mbstring 扩展版本配置文件
 * 注意：mbstring 通常是 PHP 内置扩展，PECL 版本用于特殊需求
 */

return [
    'name' => 'mbstring',
    'type' => 'pecl',
    'description' => 'Multibyte String Functions',
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
        'discovery_source' => 'https://pecl.php.net/rest/r/mbstring/allreleases.xml',
        'auto_updated' => false,
        'note' => 'mbstring is usually built-in, PECL version for special cases',
    ],
];
