<?php

/**
 * PECL Phar 扩展版本配置文件
 * 注意：Phar 从 PHP 5.3 开始内置，PECL 版本主要用于旧版本
 */

return [
    'name' => 'phar',
    'type' => 'pecl',
    'description' => 'PHP Archive',
    'version_range' => ['2.0.3', '2.0.3'],
    'all_versions' => ['2.0.3'],
    'recommended_versions' => ['2.0.3'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/phar/allreleases.xml',
        'auto_updated' => false,
        'note' => 'Phar is built-in since PHP 5.3, PECL version for older PHP versions',
    ],
];
