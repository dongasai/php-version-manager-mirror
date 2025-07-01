<?php

/**
 * PECL OpenSSL 扩展版本配置文件
 * 注意：OpenSSL 通常是 PHP 内置扩展，PECL 版本用于特殊需求
 */

return [
    'name' => 'openssl',
    'type' => 'pecl',
    'description' => 'OpenSSL cryptographic functions',
    'version_range' => ['0.9.8', '0.9.8'],
    'all_versions' => ['0.9.8'],
    'recommended_versions' => ['0.9.8'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/openssl/allreleases.xml',
        'auto_updated' => false,
        'note' => 'OpenSSL is usually built-in, PECL version for special cases',
    ],
];
