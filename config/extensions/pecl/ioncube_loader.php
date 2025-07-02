<?php

/**
 * PECL ioncube_loader 扩展版本配置文件
 */

return [
    'name' => 'ioncube_loader',
    'type' => 'pecl',
    'description' => 'ionCube PHP Encoder',
    'version_range' => ['13.0', '13.0'],
    'all_versions' => ['13.0'],
    'recommended_versions' => ['13.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/ioncube_loader/allreleases.xml',
        'auto_updated' => false,
        'note' => 'ioncube_loader is a commercial extension',
    ],
];