<?php

/**
 * PECL mssql 扩展版本配置文件
 */

return [
    'name' => 'mssql',
    'type' => 'pecl',
    'description' => 'Microsoft SQL Server functions',
    'version_range' => ['0.13', '0.13'],
    'all_versions' => ['0.13'],
    'recommended_versions' => ['0.13'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/mssql/allreleases.xml',
        'auto_updated' => false,
    ],
];