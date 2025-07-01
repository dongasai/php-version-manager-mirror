<?php

/**
 * GitHub ddtrace 扩展版本配置文件
 */

return [
    'name' => 'ddtrace',
    'type' => 'github',
    'description' => 'Datadog APM tracer',
    'repository' => 'DataDog/dd-trace-php',
    'source' => 'https://github.com/DataDog/dd-trace-php/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['0.98.0', '1.0.0'],
    'recommended_versions' => ['0.98.0', '1.0.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 2,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/DataDog/dd-trace-php/tags',
        'auto_updated' => false,
    ],
];