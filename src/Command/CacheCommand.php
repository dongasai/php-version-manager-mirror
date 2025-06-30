<?php

namespace Mirror\Command;

use Mirror\Cache\CacheManager;

/**
 * 缓存命令类
 */
class CacheCommand extends AbstractCommand
{
    /**
     * 缓存管理器
     *
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct('cache', '管理缓存');
        $this->cacheManager = new CacheManager();
    }

    /**
     * 执行命令
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    public function execute(array $args = [])
    {
        // 如果没有参数，显示帮助信息
        if (empty($args)) {
            return $this->showHelp();
        }

        // 获取操作
        $action = $args[0];

        // 执行操作
        switch ($action) {
            case 'status':
                return $this->showStatus();

            case 'enable':
                return $this->enableCache();

            case 'disable':
                return $this->disableCache();

            case 'clear':
                return $this->clearCache();

            case 'clean':
                return $this->cleanExpiredCache();

            case 'help':
                return $this->showHelp();

            default:
                echo "未知操作: $action\n";
                return $this->showHelp();
        }
    }

    /**
     * 显示缓存状态
     *
     * @return int 退出代码
     */
    private function showStatus()
    {
        // 获取缓存统计信息
        $stats = $this->cacheManager->getStats();

        echo "缓存状态:\n";
        echo "启用状态: " . ($this->cacheManager->isEnabled() ? '启用' : '禁用') . "\n";
        echo "缓存项总数: " . $stats['total'] . "\n";
        echo "有效缓存项: " . $stats['valid'] . "\n";
        echo "过期缓存项: " . $stats['expired'] . "\n";
        echo "缓存大小: " . $this->formatSize($stats['size']) . "\n";

        return 0;
    }

    /**
     * 启用缓存
     *
     * @return int 退出代码
     */
    private function enableCache()
    {
        // 获取配置管理器
        $configManager = new \Mirror\Config\ConfigManager();
        $runtimeConfig = $configManager->getRuntimeConfig();

        // 确保缓存配置存在
        if (!isset($runtimeConfig['cache'])) {
            $runtimeConfig['cache'] = [];
        }

        // 启用缓存
        $runtimeConfig['cache']['enable_cache'] = true;

        // 保存配置
        if ($configManager->saveRuntimeConfig($runtimeConfig)) {
            echo "缓存已启用\n";
            return 0;
        } else {
            echo "启用缓存失败\n";
            return 1;
        }
    }

    /**
     * 禁用缓存
     *
     * @return int 退出代码
     */
    private function disableCache()
    {
        // 获取配置管理器
        $configManager = new \Mirror\Config\ConfigManager();
        $runtimeConfig = $configManager->getRuntimeConfig();

        // 确保缓存配置存在
        if (!isset($runtimeConfig['cache'])) {
            $runtimeConfig['cache'] = [];
        }

        // 禁用缓存
        $runtimeConfig['cache']['enable_cache'] = false;

        // 保存配置
        if ($configManager->saveRuntimeConfig($runtimeConfig)) {
            echo "缓存已禁用\n";
            return 0;
        } else {
            echo "禁用缓存失败\n";
            return 1;
        }
    }

    /**
     * 清空缓存
     *
     * @return int 退出代码
     */
    private function clearCache()
    {
        if ($this->cacheManager->clear()) {
            echo "缓存已清空\n";
            return 0;
        } else {
            echo "清空缓存失败\n";
            return 1;
        }
    }

    /**
     * 清理过期缓存
     *
     * @return int 退出代码
     */
    private function cleanExpiredCache()
    {
        $count = $this->cacheManager->cleanExpired();
        echo "已清理 $count 个过期缓存项\n";
        return 0;
    }

    /**
     * 格式化文件大小
     *
     * @param int $size 文件大小（字节）
     * @return string 格式化后的大小
     */
    private function formatSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * 显示帮助信息
     *
     * @return int 退出代码
     */
    private function showHelp()
    {
        echo "缓存管理\n";
        echo "用法: pvm-mirror cache <操作>\n\n";
        echo "可用操作:\n";
        echo "  status    显示缓存状态\n";
        echo "  enable    启用缓存\n";
        echo "  disable   禁用缓存\n";
        echo "  clear     清空所有缓存\n";
        echo "  clean     清理过期缓存\n";
        echo "  help      显示此帮助信息\n\n";
        echo "示例:\n";
        echo "  pvm-mirror cache status\n";
        echo "  pvm-mirror cache enable\n";
        echo "  pvm-mirror cache clean\n";

        return 0;
    }
}
