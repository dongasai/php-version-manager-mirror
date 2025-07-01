<?php

/**
 * GitHub opentelemetry 扩展版本配置文件
 */

return [
    'name' => 'opentelemetry',
    'type' => 'github',
    'description' => 'OpenTelemetry instrumentation',
    'repository' => 'open-telemetry/opentelemetry-php-instrumentation',
    'source' => 'https://github.com/open-telemetry/opentelemetry-php-instrumentation/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['1.0.3'],
    'recommended_versions' => ['1.0.3'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/open-telemetry/opentelemetry-php-instrumentation/tags',
        'auto_updated' => false,
    ],
];