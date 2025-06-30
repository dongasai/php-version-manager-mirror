<?php

/**
 * PECL msgpack 扩展版本配置文件
 */

return [
    'name' => 'msgpack',
    'type' => 'pecl',
    'description' => 'MessagePack serializer',
    'version_range' => ['2.2.0', '2.2.0'],
    'all_versions' => ['2.2.0'],
    'recommended_versions' => ['2.2.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/msgpack/allreleases.xml',
        'auto_updated' => false,
    ],
];