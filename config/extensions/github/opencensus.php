<?php

/**
 * GitHub opencensus 扩展版本配置文件
 */

return [
    'name' => 'opencensus',
    'type' => 'github',
    'description' => 'OpenCensus tracing',
    'repository' => 'census-instrumentation/opencensus-php',
    'source' => 'https://github.com/census-instrumentation/opencensus-php/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['v0.3.0'],
    'recommended_versions' => ['v0.3.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/census-instrumentation/opencensus-php/tags',
        'auto_updated' => false,
    ],
];