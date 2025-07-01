<?php

/**
 * PECL ldap 扩展版本配置文件
 * 注意：ldap 通常是 PHP 内置扩展，PECL 版本用于特殊需求
 */

return [
    'name' => 'ldap',
    'type' => 'pecl',
    'description' => 'LDAP functions',
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
        'discovery_source' => 'https://pecl.php.net/rest/r/ldap/allreleases.xml',
        'auto_updated' => false,
        'note' => 'ldap is usually built-in, PECL version for special cases',
    ],
];