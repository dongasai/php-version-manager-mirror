<?php

namespace Mirror\Command;

use Mirror\Mirror\MirrorStatus;

/**
 * 状态命令类
 */
class StatusCommand extends AbstractCommand
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct('status', '显示镜像状态');
    }

    /**
     * 执行命令
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    public function execute(array $args = [])
    {
        echo "镜像状态:\n";

        // 获取配置
        $configManager = new \Mirror\Config\ConfigManager();
        $dataDir = $configManager->getDataDir();

        // 获取镜像状态
        $status = new MirrorStatus();
        $stats = $status->getStatus();

        // 显示状态信息
        echo "PHP 源码包: " . $stats['php_files'] . " 个文件\n";
        echo "PECL 扩展包: " . $stats['pecl_files'] . " 个文件\n";
        echo "特定扩展源码: " . count($stats['extension_dirs']) . " 个扩展, " . $stats['extension_files'] . " 个文件\n";
        echo "Composer 包: " . $stats['composer_files'] . " 个文件\n";
        echo "总大小: " . $status->formatSize($stats['total_size']) . "\n";

        if ($stats['last_update'] > 0) {
            echo "最后更新: " . date('Y-m-d H:i:s', $stats['last_update']) . "\n";
        }

        // 显示内容存储位置和配置文件位置
        echo "\n存储位置信息:\n";
        echo "内容存储位置: " . $dataDir . "\n";
        echo "镜像配置文件: " . ROOT_DIR . "/config/mirror.php\n";
        echo "运行时配置文件: " . ROOT_DIR . "/config/runtime.php\n";

        // 显示运行时配置摘要
        $runtimeConfig = $configManager->getRuntimeConfig();
        echo "\n运行时配置摘要:\n";
        echo "服务器主机: " . ($runtimeConfig['server']['host'] ?? '0.0.0.0') . "\n";
        echo "服务器端口: " . ($runtimeConfig['server']['port'] ?? '8080') . "\n";
        echo "公开URL: " . ($runtimeConfig['server']['public_url'] ?? 'http://localhost:8080') . "\n";
        echo "日志级别: " . ($runtimeConfig['log_level'] ?? 'info') . "\n";
        echo "同步间隔: " . ($runtimeConfig['sync']['interval'] ?? '24') . " 小时\n";
        echo "清理配置: 保留 " . ($runtimeConfig['cleanup']['keep_versions'] ?? '5') . " 个版本, 最小保留 " . ($runtimeConfig['cleanup']['min_age'] ?? '30') . " 天\n";

        return 0;
    }
}
