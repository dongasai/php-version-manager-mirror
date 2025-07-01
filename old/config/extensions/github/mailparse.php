<?php

/**
 * GitHub mailparse 扩展版本配置文件
 */

return [
    'name' => 'mailparse',
    'type' => 'github',
    'description' => 'Email message manipulation',
    'repository' => 'php/pecl-mail-mailparse',
    'source' => 'https://github.com/php/pecl-mail-mailparse/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['3.1.6'],
    'recommended_versions' => ['3.1.6'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/php/pecl-mail-mailparse/tags',
        'auto_updated' => false,
    ],
];