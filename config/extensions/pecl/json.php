<?php

/**
 * PECL JSON 扩展版本配置文件
 * 注意：JSON 从 PHP 5.2 开始内置，PECL 版本主要用于旧版本或特殊需求
 */

return [
    'name' => 'json',
    'type' => 'pecl',
    'description' => 'JavaScript Object Notation',
    'version_range' => ['1.2.1', '1.7.0'],
    'all_versions' => ['1.2.1', '1.4.0', '1.6.1', '1.7.0'],
    'recommended_versions' => ['1.6.1', '1.7.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 4,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/json/allreleases.xml',
        'auto_updated' => false,
        'note' => 'JSON is built-in since PHP 5.2, PECL version for older PHP versions',
    ],
];
