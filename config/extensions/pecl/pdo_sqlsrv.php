<?php

/**
 * PECL pdo_sqlsrv 扩展版本配置文件
 */

return [
    'name' => 'pdo_sqlsrv',
    'type' => 'pecl',
    'description' => 'Microsoft Drivers for PHP for SQL Server (PDO_SQLSRV)',
    'version_range' => ['5.10.1', '5.12.0'],
    'all_versions' => ['5.10.1', '5.12.0'],
    'recommended_versions' => ['5.10.1', '5.12.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 2,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://pecl.php.net/rest/r/pdo_sqlsrv/allreleases.xml',
        'auto_updated' => false,
    ],
];