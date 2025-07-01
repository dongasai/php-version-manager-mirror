<?php

namespace Mirror\Command;

use Mirror\Resource\ResourceManager;

/**
 * 资源命令类
 */
class ResourceCommand extends AbstractCommand
{
    /**
     * 资源管理器
     *
     * @var ResourceManager
     */
    private $resourceManager;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct('resource', '管理系统资源');
        $this->resourceManager = new ResourceManager();
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
                return $this->enableResourceLimits();

            case 'disable':
                return $this->disableResourceLimits();

            case 'set':
                if (count($args) < 3) {
                    echo "错误: 缺少参数\n";
                    return $this->showHelp();
                }
                return $this->setResourceLimit($args[1], $args[2]);

            case 'monitor':
                return $this->monitorResources();

            case 'help':
                return $this->showHelp();

            default:
                echo "未知操作: $action\n";
                return $this->showHelp();
        }
    }

    /**
     * 显示资源状态
     *
     * @return int 退出代码
     */
    private function showStatus()
    {
        // 获取资源状态
        $status = $this->resourceManager->getResourceStatus();
        $resources = $this->resourceManager->checkSystemResources();

        echo "资源状态:\n";
        echo "资源限制: " . ($status['enabled'] ? '启用' : '禁用') . "\n";
        echo "最大并发下载数: " . $status['max_concurrent_downloads'] . "\n";
        echo "每分钟最大请求数: " . $status['max_requests_per_minute'] . "\n";
        echo "下载速度限制: " . $status['download_speed_limit'] . "\n";
        echo "当前活动下载数: " . $resources['active_downloads'] . "\n";
        echo "IP请求计数: " . $status['ip_request_count'] . "\n";
        echo "\n";
        echo "系统资源使用情况:\n";
        echo "CPU使用率: " . $resources['cpu_usage'] . "%\n";
        echo "内存使用率: " . $resources['memory_usage'] . "%\n";
        echo "磁盘使用率: " . $resources['disk_usage'] . "%\n";

        return 0;
    }

    /**
     * 启用资源限制
     *
     * @return int 退出代码
     */
    private function enableResourceLimits()
    {
        // 获取配置管理器
        $configManager = new \Mirror\Config\ConfigManager();
        $runtimeConfig = $configManager->getRuntimeConfig();

        // 确保资源配置存在
        if (!isset($runtimeConfig['resource'])) {
            $runtimeConfig['resource'] = [];
        }

        // 启用资源限制
        $runtimeConfig['resource']['enable_resource_limits'] = true;

        // 保存配置
        if ($configManager->saveRuntimeConfig($runtimeConfig)) {
            echo "资源限制已启用\n";
            return 0;
        } else {
            echo "启用资源限制失败\n";
            return 1;
        }
    }

    /**
     * 禁用资源限制
     *
     * @return int 退出代码
     */
    private function disableResourceLimits()
    {
        // 获取配置管理器
        $configManager = new \Mirror\Config\ConfigManager();
        $runtimeConfig = $configManager->getRuntimeConfig();

        // 确保资源配置存在
        if (!isset($runtimeConfig['resource'])) {
            $runtimeConfig['resource'] = [];
        }

        // 禁用资源限制
        $runtimeConfig['resource']['enable_resource_limits'] = false;

        // 保存配置
        if ($configManager->saveRuntimeConfig($runtimeConfig)) {
            echo "资源限制已禁用\n";
            return 0;
        } else {
            echo "禁用资源限制失败\n";
            return 1;
        }
    }

    /**
     * 设置资源限制
     *
     * @param string $key 配置键
     * @param string $value 配置值
     * @return int 退出代码
     */
    private function setResourceLimit($key, $value)
    {
        // 获取配置管理器
        $configManager = new \Mirror\Config\ConfigManager();
        $runtimeConfig = $configManager->getRuntimeConfig();

        // 确保资源配置存在
        if (!isset($runtimeConfig['resource'])) {
            $runtimeConfig['resource'] = [];
        }

        // 验证配置键
        $validKeys = [
            'max_concurrent_downloads',
            'max_requests_per_minute',
            'download_speed_limit',
            'high_load_threshold',
            'high_memory_threshold',
            'high_disk_threshold',
        ];

        if (!in_array($key, $validKeys)) {
            echo "错误: 无效的配置键: $key\n";
            echo "有效的配置键: " . implode(', ', $validKeys) . "\n";
            return 1;
        }

        // 验证配置值
        if (!is_numeric($value)) {
            echo "错误: 配置值必须是数字\n";
            return 1;
        }

        // 特殊处理下载速度限制
        if ($key === 'download_speed_limit') {
            // 如果值包含单位，则转换为字节/秒
            if (preg_match('/^(\d+)(k|m|g|t)?$/i', $value, $matches)) {
                $num = (int)$matches[1];
                $unit = strtolower($matches[2] ?? '');
                
                switch ($unit) {
                    case 'k':
                        $value = $num * 1024;
                        break;
                    case 'm':
                        $value = $num * 1024 * 1024;
                        break;
                    case 'g':
                        $value = $num * 1024 * 1024 * 1024;
                        break;
                    case 't':
                        $value = $num * 1024 * 1024 * 1024 * 1024;
                        break;
                }
            }
        }

        // 设置配置值
        $runtimeConfig['resource'][$key] = (int)$value;

        // 保存配置
        if ($configManager->saveRuntimeConfig($runtimeConfig)) {
            echo "资源限制已设置: $key = $value\n";
            return 0;
        } else {
            echo "设置资源限制失败\n";
            return 1;
        }
    }

    /**
     * 监控资源使用情况
     *
     * @return int 退出代码
     */
    private function monitorResources()
    {
        echo "按 Ctrl+C 退出监控\n\n";

        // 每秒更新一次
        while (true) {
            // 清屏
            echo "\033[2J\033[;H";

            // 获取资源状态
            $status = $this->resourceManager->getResourceStatus();
            $resources = $this->resourceManager->checkSystemResources();

            echo "资源监控 (每秒更新)\n";
            echo "时间: " . date('Y-m-d H:i:s') . "\n\n";
            echo "资源状态:\n";
            echo "资源限制: " . ($status['enabled'] ? '启用' : '禁用') . "\n";
            echo "最大并发下载数: " . $status['max_concurrent_downloads'] . "\n";
            echo "每分钟最大请求数: " . $status['max_requests_per_minute'] . "\n";
            echo "下载速度限制: " . $status['download_speed_limit'] . "\n";
            echo "当前活动下载数: " . $resources['active_downloads'] . "\n";
            echo "IP请求计数: " . $status['ip_request_count'] . "\n";
            echo "\n";
            echo "系统资源使用情况:\n";
            echo "CPU使用率: " . $this->getProgressBar($resources['cpu_usage']) . " " . $resources['cpu_usage'] . "%\n";
            echo "内存使用率: " . $this->getProgressBar($resources['memory_usage']) . " " . $resources['memory_usage'] . "%\n";
            echo "磁盘使用率: " . $this->getProgressBar($resources['disk_usage']) . " " . $resources['disk_usage'] . "%\n";

            // 等待1秒
            sleep(1);
        }

        return 0;
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
        echo "资源管理\n";
        echo "用法: pvm-mirror resource <操作> [参数]\n\n";
        echo "可用操作:\n";
        echo "  status                        显示资源状态\n";
        echo "  enable                        启用资源限制\n";
        echo "  disable                       禁用资源限制\n";
        echo "  set <key> <value>             设置资源限制\n";
        echo "  monitor                       监控资源使用情况\n";
        echo "  help                          显示此帮助信息\n\n";
        echo "可设置的资源限制:\n";
        echo "  max_concurrent_downloads      最大并发下载数\n";
        echo "  max_requests_per_minute       每分钟最大请求数\n";
        echo "  download_speed_limit          下载速度限制（字节/秒，可使用k/m/g/t单位）\n";
        echo "  high_load_threshold           CPU使用率阈值（百分比）\n";
        echo "  high_memory_threshold         内存使用率阈值（百分比）\n";
        echo "  high_disk_threshold           磁盘使用率阈值（百分比）\n\n";
        echo "示例:\n";
        echo "  pvm-mirror resource status\n";
        echo "  pvm-mirror resource enable\n";
        echo "  pvm-mirror resource set max_concurrent_downloads 5\n";
        echo "  pvm-mirror resource set download_speed_limit 2m\n";
        echo "  pvm-mirror resource monitor\n";

        return 0;
    }
}
