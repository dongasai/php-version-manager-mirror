<?php

/**
 * PECL GD 扩展版本配置文件
 * 注意：GD 通常是 PHP 内置扩展，这里主要用于特殊版本或补丁
 */

return [
    'name' => 'gd',
    'type' => 'pecl',
    'description' => 'Image Processing and GD',
    'version_range' => ['2.3.3', '2.3.3'],
    'all_versions' => ['2.3.3'],
    'recommended_versions' => ['2.3.3'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/gd/allreleases.xml',
        'auto_updated' => false,
        'note' => 'GD is usually built-in, PECL version for special cases',
    ],
];
