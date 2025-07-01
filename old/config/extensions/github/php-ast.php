<?php

/**
 * GitHub php-ast 扩展版本配置文件
 */

return [
    'name' => 'php-ast',
    'type' => 'github',
    'description' => 'Extension exposing PHP 7+ abstract syntax tree',
    'repository' => 'nikic/php-ast',
    'source' => 'https://github.com/nikic/php-ast/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v1.0.16', 'v1.1.0', 'v1.1.1', 'v1.1.2'],
    'recommended_versions' => ['v1.1.0', 'v1.1.1', 'v1.1.2'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 4,
        'total_recommended' => 3,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/nikic/php-ast/tags',
        'auto_updated' => false,
    ],
];
