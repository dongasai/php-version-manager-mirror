<?php

/**
 * GitHub rdkafka 扩展版本配置文件
 */

return [
    'name' => 'rdkafka',
    'type' => 'github',
    'description' => 'Kafka client',
    'repository' => 'arnaud-lb/php-rdkafka',
    'source' => 'https://github.com/arnaud-lb/php-rdkafka/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v6.0.3'],
    'recommended_versions' => ['v6.0.3'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/arnaud-lb/php-rdkafka/tags',
        'auto_updated' => false,
    ],
];