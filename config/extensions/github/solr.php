<?php

/**
 * GitHub solr 扩展版本配置文件
 */

return [
    'name' => 'solr',
    'type' => 'github',
    'description' => 'Apache Solr client',
    'repository' => 'php/pecl-search_engine-solr',
    'source' => 'https://github.com/php/pecl-search_engine-solr/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['2.6.0'],
    'recommended_versions' => ['2.6.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/php/pecl-search_engine-solr/tags',
        'auto_updated' => false,
    ],
];