<?php

/**
 * GitHub zephir_parser 扩展版本配置文件
 */

return [
    'name' => 'zephir_parser',
    'type' => 'github',
    'description' => 'Zephir Parser',
    'repository' => 'zephir-lang/php-zephir-parser',
    'source' => 'https://github.com/zephir-lang/php-zephir-parser/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v1.6.0'],
    'recommended_versions' => ['v1.6.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/zephir-lang/php-zephir-parser/tags',
        'auto_updated' => false,
    ],
];