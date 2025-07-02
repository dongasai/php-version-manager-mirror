<?php

/**
 * GitHub mosquitto 扩展版本配置文件
 */

return [
    'name' => 'mosquitto',
    'type' => 'github',
    'description' => 'Mosquitto MQTT client',
    'repository' => 'mgdm/Mosquitto-PHP',
    'source' => 'https://github.com/mgdm/Mosquitto-PHP/archive/refs/tags',
    'pattern' => '{version}.tar.gz',
    'all_versions' => ['0.4.0'],
    'recommended_versions' => ['0.4.0'],
    'filter' => [
        'stable_only' => true,
        'exclude_patterns' => ['/alpha/', '/beta/', '/RC/'],
    ],
    'metadata' => [
        'total_discovered' => 1,
        'total_recommended' => 1,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/mgdm/Mosquitto-PHP/tags',
        'auto_updated' => false,
    ],
];