<?php

/**
 * GitHub Yaf 扩展版本配置文件
 */

return [
    'name' => 'yaf',
    'type' => 'github',
    'description' => 'Yet Another Framework - A fast, light, secure PHP framework',
    'repository' => 'laruence/yaf',
    'source' => 'https://github.com/laruence/yaf/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['yaf-3.3.5', 'yaf-3.3.6'],
    'recommended_versions' => ['yaf-3.3.5', 'yaf-3.3.6'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 2,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/laruence/yaf/tags',
        'auto_updated' => false,
    ],
];
