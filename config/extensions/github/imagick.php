<?php

/**
 * imagick 版本配置文件
 * 
 * 此文件由版本发现服务自动更新
 * 最后更新时间: 2025-05-29 00:04:15
 */

return [
    'name' => 'imagick',
    'type' => 'github',
    'description' => 'Provides a wrapper to the ImageMagick library',
    'repository' => 'Imagick/imagick',
    'source' => 'https://github.com/Imagick/imagick/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => [
        '3.1.0',
        '3.1.1',
        '3.1.2',
        '3.3.0',
        '3.4.2',
        '3.4.3',
        '3.4.4',
        '3.5.0',
        '3.5.1',
        '3.7.0',
        '3.8.0',
    ],
    'recommended_versions' => [
        '3.5.1',
        '3.7.0',
        '3.8.0',
    ],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => [
            '/alpha/',
            '/beta/',
            '/RC/',
        ],
    ],
    'metadata' => [
        'total_discovered' => 11,
        'total_recommended' => 3,
        'last_updated' => '2025-05-29 00:04:15',
        'discovery_source' => 'https://api.github.com/repos/Imagick/imagick/tags',
        'auto_updated' => true,
    ],
];
