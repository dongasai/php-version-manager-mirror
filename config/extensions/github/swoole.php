<?php

/**
 * GitHub Swoole 扩展版本配置文件
 * 
 * 此文件由版本发现服务自动更新
 * 最后更新时间: <?= date('Y-m-d H:i:s') ?>
 */

return [
    // 扩展基本信息
    'name' => 'swoole',
    'type' => 'github',
    'description' => 'Event-driven asynchronous and concurrent networking engine',
    'repository' => 'swoole/swoole-src',

    // GitHub 源配置
    'source' => 'https://github.com/swoole/swoole-src/archive/refs/tags',
    'pattern' => '{version}.tar.gz',

    // 所有可用版本（由版本发现服务更新）
    'all_versions' => [
        'v4.8.13',
        'v5.0.3',
    ],

    // 推荐版本（用于同步）
    'recommended_versions' => [
        'v4.8.13',
        'v5.0.3',
    ],

    // 版本过滤规则
    'filter' => [
        // 是否只包含稳定版本
        'stable_only' => true,
        // 排除的版本模式
        'exclude_patterns' => [
            '/alpha/',
            '/beta/',
            '/RC/',
        ],
        // 智能版本选择配置
        'smart_selection' => [
            'enabled' => true,
            'max_versions_per_major' => 3,
            'max_total_versions' => 20,
        ],
    ],

    // 元数据
    'metadata' => [
        'total_discovered' => 2,
        'total_recommended' => 2,
        'last_updated' => null,
        'discovery_source' => 'https://api.github.com/repos/swoole/swoole-src/tags',
        'auto_updated' => false,
    ],
];
