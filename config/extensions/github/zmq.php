<?php

/**
 * GitHub zmq 扩展版本配置文件
 */

return [
    'name' => 'zmq',
    'type' => 'github',
    'description' => 'ZeroMQ messaging',
    'repository' => 'zeromq/php-zmq',
    'source' => 'https://github.com/zeromq/php-zmq/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v1.1.3'],
    'recommended_versions' => ['v1.1.3'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/zeromq/php-zmq/tags',
        'auto_updated' => false,
    ],
];