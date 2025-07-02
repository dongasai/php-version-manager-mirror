<?php

/**
 * GitHub php_trie 扩展版本配置文件
 */

return [
    'name' => 'php_trie',
    'type' => 'github',
    'description' => 'Trie tree implementation',
    'repository' => 'ace411/php-trie-tree',
    'source' => 'https://github.com/ace411/php-trie-tree/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v1.0.0'],
    'recommended_versions' => ['v1.0.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/ace411/php-trie-tree/tags',
        'auto_updated' => false,
    ],
];