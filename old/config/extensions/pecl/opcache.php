<?php

/**
 * PECL OPcache 扩展版本配置文件
 * 注意：OPcache 从 PHP 5.5 开始内置，但仍可通过 PECL 安装旧版本
 */

return [
    'name' => 'opcache',
    'type' => 'pecl',
    'description' => 'Zend OPcache',
    'version_range' => ['7.0.6', '7.0.6'],
    'all_versions' => ['7.0.6'],
    'recommended_versions' => ['7.0.6'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/zendopcache/allreleases.xml',
        'auto_updated' => false,
        'note' => 'OPcache is built-in since PHP 5.5, PECL version for older PHP versions',
    ],
];
