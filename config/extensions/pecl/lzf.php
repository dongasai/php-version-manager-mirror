<?php

/**
 * PECL lzf 扩展版本配置文件
 */

return [
    'name' => 'lzf',
    'type' => 'pecl',
    'description' => 'LZF compression',
    'version_range' => ['1.7.0', '1.7.0'],
    'all_versions' => ['1.7.0'],
    'recommended_versions' => ['1.7.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/lzf/allreleases.xml',
        'auto_updated' => false,
    ],
];