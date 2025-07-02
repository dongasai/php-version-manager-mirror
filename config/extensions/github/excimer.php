<?php

/**
 * GitHub excimer 扩展版本配置文件
 */

return [
    'name' => 'excimer',
    'type' => 'github',
    'description' => 'Interrupting timer and low-overhead sampling profiler',
    'repository' => 'wikimedia/mediawiki-php-excimer',
    'source' => 'https://github.com/wikimedia/mediawiki-php-excimer/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v1.2.1'],
    'recommended_versions' => ['v1.2.1'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/wikimedia/mediawiki-php-excimer/tags',
        'auto_updated' => false,
    ],
];