<?php

/**
 * Composer 版本配置文件
 * 
 * 此文件由版本发现服务自动更新
 * 最后更新时间: <?= date('Y-m-d H:i:s') ?>
 */

return [
    // 版本列表
    'versions' => [
        'stable',
        '2.2.21',
        '2.3.10',
        '2.4.4',
        '2.5.8',
        '2.6.5',
        '2.7.9',
        '2.8.9',
    ],

    // 版本过滤规则
    'filter' => [
        // 是否包含稳定版本标识
        'include_stable' => true,
        // 排除的版本模式
        'exclude_patterns' => [
            '/alpha/',
            '/beta/',
            '/RC/',
        ],
    ],

    // 元数据
    'metadata' => [
        'total_versions' => 8,
        'last_updated' => null,
        'discovery_source' => 'https://getcomposer.org/download',
        'auto_updated' => false,
    ],
];
