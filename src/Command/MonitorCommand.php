<?php

namespace Mirror\Command;

use Mirror\Monitor\MonitorManager;

/**
 * 监控命令类
 */
class MonitorCommand extends AbstractCommand
{
    /**
     * 监控管理器
     *
     * @var MonitorManager
     */
    private $monitorManager;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct('monitor', '监控系统状态');
        $this->monitorManager = new MonitorManager();
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

            case 'health':
                return $this->showHealth();

            case 'stats':
                return $this->showStats();

            case 'watch':
                $interval = isset($args[1]) ? (int)$args[1] : 5;
                return $this->watchSystem($interval);

            case 'clear':
                return $this->clearData();

            case 'help':
                return $this->showHelp();

            default:
                echo "未知操作: $action\n";
                return $this->showHelp();
        }
    }

    /**
     * 显示系统状态
     *
     * @return int 退出代码
     */
    private function showStatus()
    {
        // 获取最新监控数据
        $data = $this->monitorManager->getLatestData();

        echo "系统状态:\n";
        echo "时间: " . $data['date'] . "\n\n";
        echo "系统资源:\n";
        echo "  CPU使用率: " . $this->formatPercent($data['resources']['cpu_usage']) . "\n";
        echo "  内存使用率: " . $this->formatPercent($data['resources']['memory_usage']) . "\n";
        echo "  磁盘使用率: " . $this->formatPercent($data['resources']['disk_usage']) . "\n";
        echo "  活动下载数: " . $data['resources']['active_downloads'] . "\n\n";
        echo "镜像状态:\n";
        echo "  总大小: " . $this->formatSize($data['mirror']['total_size']) . "\n";
        echo "  总文件数: " . $data['mirror']['total_files'] . "\n";
        echo "  最后更新: " . date('Y-m-d H:i:s', $data['mirror']['last_update']) . "\n";
        echo "  PHP版本数: " . $data['mirror']['php_versions'] . "\n";
        echo "  PECL扩展数: " . $data['mirror']['pecl_extensions'] . "\n";
        echo "  特定扩展数: " . $data['mirror']['extensions'] . "\n";
        echo "  Composer包数: " . $data['mirror']['composer_packages'] . "\n";

        return 0;
    }

    /**
     * 显示系统健康状态
     *
     * @return int 退出代码
     */
    private function showHealth()
    {
        // 获取健康状态
        $health = $this->monitorManager->checkHealth();

        echo "系统健康状态:\n";
        echo "时间: " . $health['date'] . "\n\n";
        echo "总体状态: " . $this->formatHealthStatus($health['overall']) . "\n";
        echo "CPU状态: " . $this->formatHealthStatus($health['cpu']) . "\n";
        echo "内存状态: " . $this->formatHealthStatus($health['memory']) . "\n";
        echo "磁盘状态: " . $this->formatHealthStatus($health['disk']) . "\n";
        echo "镜像状态: " . $this->formatHealthStatus($health['mirror']) . "\n";

        return 0;
    }

    /**
     * 显示统计数据
     *
     * @return int 退出代码
     */
    private function showStats()
    {
        // 获取统计数据
        $stats = $this->monitorManager->getStats();

        echo "系统统计数据 (24小时):\n";
        echo "CPU使用率:\n";
        echo "  当前: " . $this->formatPercent($stats['latest']['resources']['cpu_usage']) . "\n";
        echo "  平均: " . $this->formatPercent($stats['avg_cpu']) . "\n";
        echo "  最大: " . $this->formatPercent($stats['max_cpu']) . "\n";
        echo "  最小: " . $this->formatPercent($stats['min_cpu']) . "\n\n";
        echo "内存使用率:\n";
        echo "  当前: " . $this->formatPercent($stats['latest']['resources']['memory_usage']) . "\n";
        echo "  平均: " . $this->formatPercent($stats['avg_memory']) . "\n";
        echo "  最大: " . $this->formatPercent($stats['max_memory']) . "\n";
        echo "  最小: " . $this->formatPercent($stats['min_memory']) . "\n\n";
        echo "磁盘使用率:\n";
        echo "  当前: " . $this->formatPercent($stats['latest']['resources']['disk_usage']) . "\n";
        echo "  平均: " . $this->formatPercent($stats['avg_disk']) . "\n";
        echo "  最大: " . $this->formatPercent($stats['max_disk']) . "\n";
        echo "  最小: " . $this->formatPercent($stats['min_disk']) . "\n";

        return 0;
    }

    /**
     * 实时监控系统
     *
     * @param int $interval 刷新间隔（秒）
     * @return int 退出代码
     */
    private function watchSystem($interval = 5)
    {
        echo "实时系统监控 (每 $interval 秒刷新一次)\n";
        echo "按 Ctrl+C 退出\n\n";

        // 设置无限执行时间
        set_time_limit(0);

        // 循环监控
        while (true) {
            // 清屏
            echo "\033[2J\033[;H";

            // 获取最新监控数据
            $data = $this->monitorManager->collectData();

            // 获取健康状态
            $health = $this->monitorManager->checkHealth();

            echo "实时系统监控 (每 $interval 秒刷新一次)\n";
            echo "时间: " . $data['date'] . "\n\n";
            echo "系统健康状态: " . $this->formatHealthStatus($health['overall']) . "\n\n";
            echo "系统资源:\n";
            echo "  CPU使用率: " . $this->getProgressBar($data['resources']['cpu_usage']) . " " . $this->formatPercent($data['resources']['cpu_usage']) . "\n";
            echo "  内存使用率: " . $this->getProgressBar($data['resources']['memory_usage']) . " " . $this->formatPercent($data['resources']['memory_usage']) . "\n";
            echo "  磁盘使用率: " . $this->getProgressBar($data['resources']['disk_usage']) . " " . $this->formatPercent($data['resources']['disk_usage']) . "\n";
            echo "  活动下载数: " . $data['resources']['active_downloads'] . "\n\n";
            echo "镜像状态:\n";
            echo "  总大小: " . $this->formatSize($data['mirror']['total_size']) . "\n";
            echo "  总文件数: " . $data['mirror']['total_files'] . "\n";
            echo "  最后更新: " . date('Y-m-d H:i:s', $data['mirror']['last_update']) . "\n";
            echo "  PHP版本数: " . $data['mirror']['php_versions'] . "\n";
            echo "  PECL扩展数: " . $data['mirror']['pecl_extensions'] . "\n";
            echo "  特定扩展数: " . $data['mirror']['extensions'] . "\n";
            echo "  Composer包数: " . $data['mirror']['composer_packages'] . "\n";

            // 等待指定时间
            sleep($interval);
        }

        return 0;
    }

    /**
     * 清空监控数据
     *
     * @return int 退出代码
     */
    private function clearData()
    {
        if ($this->monitorManager->clearData()) {
            echo "监控数据已清空\n";
            return 0;
        } else {
            echo "清空监控数据失败\n";
            return 1;
        }
    }

    /**
     * 格式化百分比
     *
     * @param float $percent 百分比
     * @return string 格式化后的百分比
     */
    private function formatPercent($percent)
    {
        return round($percent, 2) . '%';
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
     * 格式化健康状态
     *
     * @param string $status 健康状态
     * @return string 格式化后的状态
     */
    private function formatHealthStatus($status)
    {
        switch ($status) {
            case 'healthy':
                return "\033[32m健康\033[0m";
            case 'warning':
                return "\033[33m警告\033[0m";
            case 'critical':
                return "\033[31m严重\033[0m";
            case 'normal':
                return "\033[32m正常\033[0m";
            case 'high':
                return "\033[33m偏高\033[0m";
            case 'outdated':
                return "\033[33m过期\033[0m";
            default:
                return $status;
        }
    }

    /**
     * 获取进度条
     *
     * @param float $percent 百分比
     * @return string 进度条
     */
    private function getProgressBar($percent)
    {
        $width = 20;
        $completed = floor($percent / 100 * $width);
        $remaining = $width - $completed;

        // 根据百分比选择颜色
        if ($percent < 50) {
            $color = "\033[32m"; // 绿色
        } elseif ($percent < 80) {
            $color = "\033[33m"; // 黄色
        } else {
            $color = "\033[31m"; // 红色
        }

        $reset = "\033[0m";

        return "[" . $color . str_repeat("=", $completed) . $reset . str_repeat(" ", $remaining) . "]";
    }

    /**
     * 显示帮助信息
     *
     * @return int 退出代码
     */
    private function showHelp()
    {
        echo "系统监控\n";
        echo "用法: pvm-mirror monitor <操作> [参数]\n\n";
        echo "可用操作:\n";
        echo "  status           显示系统状态\n";
        echo "  health           显示系统健康状态\n";
        echo "  stats            显示统计数据\n";
        echo "  watch [间隔]     实时监控系统（默认5秒刷新一次）\n";
        echo "  clear            清空监控数据\n";
        echo "  help             显示此帮助信息\n\n";
        echo "示例:\n";
        echo "  pvm-mirror monitor status\n";
        echo "  pvm-mirror monitor health\n";
        echo "  pvm-mirror monitor watch 3\n";

        return 0;
    }
}
