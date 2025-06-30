<?php

/**
 * GitHub xlswriter 扩展版本配置文件
 */

return [
    'name' => 'xlswriter',
    'type' => 'github',
    'description' => 'Excel writer',
    'repository' => 'viest/php-ext-xlswriter',
    'source' => 'https://github.com/viest/php-ext-xlswriter/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v1.5.5'],
    'recommended_versions' => ['v1.5.5'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/viest/php-ext-xlswriter/tags',
        'auto_updated' => false,
    ],
];