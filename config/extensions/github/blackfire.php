<?php

/**
 * GitHub blackfire 扩展版本配置文件
 */

return [
    'name' => 'blackfire',
    'type' => 'github',
    'description' => 'Blackfire Profiler',
    'repository' => 'blackfireio/php-sdk',
    'source' => 'https://github.com/blackfireio/php-sdk/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v1.92.15'],
    'recommended_versions' => ['v1.92.15'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/blackfireio/php-sdk/tags',
        'auto_updated' => false,
    ],
];