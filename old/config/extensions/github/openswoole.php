<?php

/**
 * GitHub openswoole 扩展版本配置文件
 */

return [
    'name' => 'openswoole',
    'type' => 'github',
    'description' => 'Open Swoole',
    'repository' => 'openswoole/swoole-src',
    'source' => 'https://github.com/openswoole/swoole-src/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v4.12.1', 'v22.1.2'],
    'recommended_versions' => ['v4.12.1', 'v22.1.2'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 2,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/openswoole/swoole-src/tags',
        'auto_updated' => false,
    ],
];