<?php

/**
 * GitHub xhprof 扩展版本配置文件
 */

return [
    'name' => 'xhprof',
    'type' => 'github',
    'description' => 'Hierarchical Profiler',
    'repository' => 'longxinH/xhprof',
    'source' => 'https://github.com/longxinH/xhprof/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v2.3.9'],
    'recommended_versions' => ['v2.3.9'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/longxinH/xhprof/tags',
        'auto_updated' => false,
    ],
];