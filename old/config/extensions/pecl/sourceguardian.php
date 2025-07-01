<?php

/**
 * PECL sourceguardian 扩展版本配置文件
 */

return [
    'name' => 'sourceguardian',
    'type' => 'pecl',
    'description' => 'SourceGuardian PHP encoder',
    'version_range' => ['14.0', '14.0'],
    'all_versions' => ['14.0'],
    'recommended_versions' => ['14.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/sourceguardian/allreleases.xml',
        'auto_updated' => false,
        'note' => 'sourceguardian is a commercial extension',
    ],
];