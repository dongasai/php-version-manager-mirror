<?php

/**
 * PECL smbclient 扩展版本配置文件
 */

return [
    'name' => 'smbclient',
    'type' => 'pecl',
    'description' => 'SMB client library',
    'version_range' => ['1.0.6', '1.1.1'],
    'all_versions' => ['1.0.6', '1.1.1'],
    'recommended_versions' => ['1.0.6', '1.1.1'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 2,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/smbclient/allreleases.xml',
        'auto_updated' => false,
    ],
];