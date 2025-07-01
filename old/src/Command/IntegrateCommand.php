<?php

namespace Mirror\Command;

use Mirror\Integration\IntegrationManager;
use Mirror\Monitor\MonitorManager;

/**
 * 集成命令类
 */
class IntegrateCommand extends AbstractCommand
{
    /**
     * 集成管理器
     *
     * @var IntegrationManager
     */
    private $integrationManager;

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
        parent::__construct('integrate', '与PVM集成');
        $this->integrationManager = new IntegrationManager();
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
            case 'configure':
                return $this->configurePvm();

            case 'status':
                return $this->showStatus();

            case 'health':
                return $this->checkHealth();

            case 'switch':
                if (count($args) < 2) {
                    echo "错误: 缺少镜像类型\n";
                    return $this->showHelp();
                }
                $type = $args[1];
                $mirror = isset($args[2]) ? $args[2] : 'official';
                return $this->switchMirror($type, $mirror);

            case 'help':
                return $this->showHelp();

            default:
                echo "未知操作: $action\n";
                return $this->showHelp();
        }
    }

    /**
     * 配置PVM使用本地镜像
     *
     * @return int 退出代码
     */
    private function configurePvm()
    {
        echo "正在配置PVM使用本地镜像...\n";

        if ($this->integrationManager->configurePvm()) {
            echo "配置成功！PVM现在将使用本地镜像。\n";
            return 0;
        } else {
            echo "配置失败！请检查权限和PVM安装状态。\n";
            return 1;
        }
    }

    /**
     * 显示集成状态
     *
     * @return int 退出代码
     */
    private function showStatus()
    {
        // 检查PVM是否已配置使用本地镜像
        $isConfigured = $this->integrationManager->isPvmConfigured();

        echo "PVM集成状态:\n";
        echo "PVM配置状态: " . ($isConfigured ? '已配置使用本地镜像' : '未配置使用本地镜像') . "\n";

        // 获取PVM镜像配置
        $mirrorConfig = $this->integrationManager->getPvmMirrorConfig();
        if ($mirrorConfig !== null) {
            echo "\nPVM镜像配置:\n";

            // 显示PHP镜像配置
            echo "PHP镜像:\n";
            echo "  默认: " . $mirrorConfig['php']['default'] . "\n";
            echo "  镜像列表:\n";
            echo "    official: " . $mirrorConfig['php']['official'] . "\n";
            foreach ($mirrorConfig['php']['mirrors'] as $name => $url) {
                echo "    $name: $url\n";
            }

            // 显示PECL镜像配置
            echo "\nPECL镜像:\n";
            echo "  默认: " . $mirrorConfig['pecl']['default'] . "\n";
            echo "  镜像列表:\n";
            echo "    official: " . $mirrorConfig['pecl']['official'] . "\n";
            foreach ($mirrorConfig['pecl']['mirrors'] as $name => $url) {
                echo "    $name: $url\n";
            }

            // 显示Composer镜像配置
            echo "\nComposer镜像:\n";
            echo "  默认: " . $mirrorConfig['composer']['default'] . "\n";
            echo "  镜像列表:\n";
            echo "    official: " . $mirrorConfig['composer']['official'] . "\n";
            foreach ($mirrorConfig['composer']['mirrors'] as $name => $url) {
                echo "    $name: $url\n";
            }

            // 显示扩展镜像配置
            if (isset($mirrorConfig['extensions'])) {
                echo "\n扩展镜像:\n";
                foreach ($mirrorConfig['extensions'] as $extension => $config) {
                    echo "  $extension:\n";
                    echo "    默认: " . $config['default'] . "\n";
                    echo "    镜像列表:\n";
                    echo "      official: " . $config['official'] . "\n";
                    foreach ($config['mirrors'] as $name => $url) {
                        echo "      $name: $url\n";
                    }
                }
            }
        } else {
            echo "\nPVM镜像配置文件不存在。\n";
        }

        return 0;
    }

    /**
     * 检查镜像健康状态
     *
     * @return int 退出代码
     */
    private function checkHealth()
    {
        // 获取健康状态
        $health = $this->integrationManager->checkMirrorHealth();

        echo "镜像健康状态:\n";
        echo "时间: " . $health['date'] . "\n\n";
        echo "总体状态: " . $this->formatHealthStatus($health['overall']) . "\n";
        echo "CPU状态: " . $this->formatHealthStatus($health['cpu']) . "\n";
        echo "内存状态: " . $this->formatHealthStatus($health['memory']) . "\n";
        echo "磁盘状态: " . $this->formatHealthStatus($health['disk']) . "\n";
        echo "镜像状态: " . $this->formatHealthStatus($health['mirror']) . "\n";

        // 如果镜像状态不正常，提供建议
        if ($health['mirror'] !== 'normal') {
            echo "\n镜像状态异常，建议执行以下操作:\n";
            echo "1. 检查镜像同步状态: pvm-mirror status\n";
            echo "2. 重新同步镜像: pvm-mirror sync\n";
            echo "3. 如果问题仍然存在，可以切换到官方镜像: pvm-mirror integrate switch all official\n";
        }

        return 0;
    }

    /**
     * 切换镜像
     *
     * @param string $type 镜像类型
     * @param string $mirror 镜像名称
     * @return int 退出代码
     */
    private function switchMirror($type, $mirror)
    {
        // 如果类型是all，则切换所有镜像
        if ($type === 'all') {
            echo "正在切换所有镜像到 $mirror...\n";
            if ($this->integrationManager->switchAllMirrors($mirror)) {
                echo "切换成功！所有镜像已切换到 $mirror。\n";
                return 0;
            } else {
                echo "切换失败！请检查镜像配置。\n";
                return 1;
            }
        }

        // 如果类型是extension，则需要额外的扩展名称参数
        if ($type === 'extension') {
            if (count($args) < 3) {
                echo "错误: 缺少扩展名称\n";
                echo "用法: pvm-mirror integrate switch extension <扩展名称> [镜像名称]\n";
                return 1;
            }
            $extension = $args[2];
            $mirror = isset($args[3]) ? $args[3] : 'official';

            echo "正在切换扩展 $extension 的镜像到 $mirror...\n";
            if ($this->integrationManager->switchExtensionMirror($extension, $mirror)) {
                echo "切换成功！扩展 $extension 的镜像已切换到 $mirror。\n";
                return 0;
            } else {
                echo "切换失败！请检查扩展和镜像配置。\n";
                return 1;
            }
        }

        // 其他类型的镜像
        echo "正在切换 $type 镜像到 $mirror...\n";
        if ($this->integrationManager->switchMirror($type, $mirror)) {
            echo "切换成功！$type 镜像已切换到 $mirror。\n";
            return 0;
        } else {
            echo "切换失败！请检查镜像类型和名称。\n";
            return 1;
        }
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
     * 显示帮助信息
     *
     * @return int 退出代码
     */
    private function showHelp()
    {
        echo "PVM集成管理\n";
        echo "用法: pvm-mirror integrate <操作> [参数]\n\n";
        echo "可用操作:\n";
        echo "  configure              配置PVM使用本地镜像\n";
        echo "  status                 显示集成状态\n";
        echo "  health                 检查镜像健康状态\n";
        echo "  switch <类型> [镜像]    切换镜像\n";
        echo "  help                   显示此帮助信息\n\n";
        echo "镜像类型:\n";
        echo "  php                    PHP下载镜像\n";
        echo "  pecl                   PECL扩展下载镜像\n";
        echo "  extension <扩展名称>    特定扩展下载镜像\n";
        echo "  composer               Composer下载镜像\n";
        echo "  all                    所有镜像\n\n";
        echo "示例:\n";
        echo "  pvm-mirror integrate configure\n";
        echo "  pvm-mirror integrate status\n";
        echo "  pvm-mirror integrate health\n";
        echo "  pvm-mirror integrate switch php local\n";
        echo "  pvm-mirror integrate switch all official\n";

        return 0;
    }
}
