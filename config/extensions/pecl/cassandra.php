<?php

/**
 * PECL cassandra 扩展版本配置文件
 */

return [
    'name' => 'cassandra',
    'type' => 'pecl',
    'description' => 'DataStax PHP Driver for Apache Cassandra',
    'version_range' => ['1.3.2', '1.3.2'],
    'all_versions' => ['1.3.2'],
    'recommended_versions' => ['1.3.2'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/cassandra/allreleases.xml',
        'auto_updated' => false,
    ],
];