<?php

/**
 * GitHub yar 扩展版本配置文件
 */

return [
    'name' => 'yar',
    'type' => 'github',
    'description' => 'Yet Another RPC framework',
    'repository' => 'laruence/yar',
    'source' => 'https://github.com/laruence/yar/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['yar-2.3.3'],
    'recommended_versions' => ['yar-2.3.3'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/laruence/yar/tags',
        'auto_updated' => false,
    ],
];