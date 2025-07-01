<?php

/**
 * GitHub snuffleupagus 扩展版本配置文件
 */

return [
    'name' => 'snuffleupagus',
    'type' => 'github',
    'description' => 'Security module',
    'repository' => 'jvoisin/snuffleupagus',
    'source' => 'https://github.com/jvoisin/snuffleupagus/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v0.10.0'],
    'recommended_versions' => ['v0.10.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/jvoisin/snuffleupagus/tags',
        'auto_updated' => false,
    ],
];