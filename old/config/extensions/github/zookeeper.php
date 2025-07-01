<?php

/**
 * GitHub zookeeper 扩展版本配置文件
 */

return [
    'name' => 'zookeeper',
    'type' => 'github',
    'description' => 'Apache Zookeeper client',
    'repository' => 'php-zookeeper/php-zookeeper',
    'source' => 'https://github.com/php-zookeeper/php-zookeeper/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v1.2.0'],
    'recommended_versions' => ['v1.2.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/php-zookeeper/php-zookeeper/tags',
        'auto_updated' => false,
    ],
];