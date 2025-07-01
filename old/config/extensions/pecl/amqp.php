<?php

/**
 * PECL amqp 扩展版本配置文件
 */

return [
    'name' => 'amqp',
    'type' => 'pecl',
    'description' => 'AMQP client library',
    'version_range' => ['1.11.0', '2.1.2'],
    'all_versions' => ['1.11.0', '2.1.2'],
    'recommended_versions' => ['1.11.0', '2.1.2'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 2,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/amqp/allreleases.xml',
        'auto_updated' => false,
    ],
];