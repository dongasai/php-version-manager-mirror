<?php

/**
 * GitHub ion 扩展版本配置文件
 */

return [
    'name' => 'ion',
    'type' => 'github',
    'description' => 'Amazon Ion data notation',
    'repository' => 'awesomized/ext-ion',
    'source' => 'https://github.com/awesomized/ext-ion/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v0.2.1'],
    'recommended_versions' => ['v0.2.1'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/awesomized/ext-ion/tags',
        'auto_updated' => false,
    ],
];