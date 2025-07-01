<?php

/**
 * PECL geoip 扩展版本配置文件
 */

return [
    'name' => 'geoip',
    'type' => 'pecl',
    'description' => 'GeoIP location',
    'version_range' => ['1.1.1', '1.1.1'],
    'all_versions' => ['1.1.1'],
    'recommended_versions' => ['1.1.1'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/geoip/allreleases.xml',
        'auto_updated' => false,
    ],
];