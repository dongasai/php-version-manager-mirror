<?php

/**
 * PECL timezonedb 扩展版本配置文件
 */

return [
    'name' => 'timezonedb',
    'type' => 'pecl',
    'description' => 'Timezone database',
    'version_range' => ['2024.1', '2024.1'],
    'all_versions' => ['2024.1'],
    'recommended_versions' => ['2024.1'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/timezonedb/allreleases.xml',
        'auto_updated' => false,
    ],
];