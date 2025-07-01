<?php

namespace Mirror\Command;

use Mirror\Server\ServerManager;

/**
 * 服务器命令类
 */
class ServerCommand extends AbstractCommand
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct('server', '管理镜像服务器');
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

        // 移除操作参数
        array_shift($args);

        // 检查是否有前台运行选项
        $foreground = false;
        $argIndex = array_search('-f', $args);
        if ($argIndex !== false) {
            $foreground = true;
            unset($args[$argIndex]);
            $args = array_values($args); // 重新索引数组
        }

        $argIndex = array_search('--foreground', $args);
        if ($argIndex !== false) {
            $foreground = true;
            unset($args[$argIndex]);
            $args = array_values($args); // 重新索引数组
        }

        // 获取端口
        $port = isset($args[0]) ? (int)$args[0] : 0;

        // 如果端口为0，则从配置文件中获取
        if ($port === 0) {
            $configManager = new \Mirror\Config\ConfigManager();
            $serverConfig = $configManager->getServerConfig();
            $port = $serverConfig['port'] ?? 8080;
        }

        // 创建服务器管理器
        $serverManager = new ServerManager();

        // 执行操作
        switch ($action) {
            case 'start':
                $serverManager->start($port, $foreground);
                break;

            case 'stop':
                $serverManager->stop();
                break;

            case 'status':
                $serverManager->status();
                break;

            case 'restart':
                $serverManager->restart($port, $foreground);
                break;

            case 'help':
                return $this->showHelp();

            default:
                echo "未知操作: $action\n";
                return $this->showHelp();
        }

        return 0;
    }

    /**
     * 显示帮助信息
     *
     * @return int 退出代码
     */
    private function showHelp()
    {
        echo "PVM 镜像服务器管理\n";
        echo "用法: pvm-mirror server <操作> [选项] [端口]\n\n";
        echo "可用操作:\n";
        echo "  start   启动服务器 (默认端口: 8080)\n";
        echo "  stop    停止服务器\n";
        echo "  status  显示服务器状态\n";
        echo "  restart 重启服务器\n";
        echo "  help    显示此帮助信息\n\n";
        echo "选项:\n";
        echo "  -f, --foreground  在前台运行服务器（仅适用于start操作）\n\n";
        echo "示例:\n";
        echo "  pvm-mirror server start                # 在默认端口 8080 上后台启动服务器\n";
        echo "  pvm-mirror server start 9000           # 在端口 9000 上后台启动服务器\n";
        echo "  pvm-mirror server start -f             # 在默认端口 8080 上前台启动服务器\n";
        echo "  pvm-mirror server start --foreground   # 在默认端口 8080 上前台启动服务器\n";
        echo "  pvm-mirror server start -f 9000        # 在端口 9000 上前台启动服务器\n";
        echo "  pvm-mirror server stop                 # 停止服务器\n";

        return 0;
    }
}
