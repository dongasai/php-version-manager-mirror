<?php

/**
 * GitHub Tideways 扩展版本配置文件
 */

return [
    'name' => 'tideways',
    'type' => 'github',
    'description' => 'Tideways PHP Profiler Extension',
    'repository' => 'tideways/php-xhprof-extension',
    'source' => 'https://github.com/tideways/php-xhprof-extension/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v5.0.4', 'v5.0.5'],
    'recommended_versions' => ['v5.0.4', 'v5.0.5'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 2,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/tideways/php-xhprof-extension/tags',
        'auto_updated' => false,
    ],
];
