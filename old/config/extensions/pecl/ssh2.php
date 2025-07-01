<?php

/**
 * PECL ssh2 扩展版本配置文件
 */

return [
    'name' => 'ssh2',
    'type' => 'pecl',
    'description' => 'SSH2 functions',
    'version_range' => ['1.3.1', '1.4.1'],
    'all_versions' => ['1.3.1', '1.4.1'],
    'recommended_versions' => ['1.3.1', '1.4.1'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 2,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/ssh2/allreleases.xml',
        'auto_updated' => false,
    ],
];