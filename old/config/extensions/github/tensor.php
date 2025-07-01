<?php

/**
 * GitHub tensor 扩展版本配置文件
 */

return [
    'name' => 'tensor',
    'type' => 'github',
    'description' => 'Scientific computing library',
    'repository' => 'RubixML/Tensor',
    'source' => 'https://github.com/RubixML/Tensor/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v3.0.4'],
    'recommended_versions' => ['v3.0.4'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/RubixML/Tensor/tags',
        'auto_updated' => false,
    ],
];