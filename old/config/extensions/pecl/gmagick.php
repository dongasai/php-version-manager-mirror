<?php

/**
 * PECL gmagick 扩展版本配置文件
 */

return [
    'name' => 'gmagick',
    'type' => 'pecl',
    'description' => 'GraphicsMagick binding',
    'version_range' => ['2.0.6RC1', '2.0.6RC1'],
    'all_versions' => ['2.0.6RC1'],
    'recommended_versions' => ['2.0.6RC1'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/gmagick/allreleases.xml',
        'auto_updated' => false,
    ],
];