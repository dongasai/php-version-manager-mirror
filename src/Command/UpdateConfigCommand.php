<?php

namespace Mirror\Command;

use Mirror\Service\ConfigUpdateService;

/**
 * 配置更新命令类
 */
class UpdateConfigCommand extends AbstractCommand
{
    private $updateService;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct('update-config', '更新配置文件中的版本信息');
        $this->updateService = new ConfigUpdateService();
    }

    /**
     * 执行命令
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    public function execute(array $args = [])
    {
        // 检查是否为试运行模式
        $dryRun = in_array('--dry-run', $args) || in_array('-n', $args);
        
        // 移除试运行参数
        $args = array_filter($args, function($arg) {
            return $arg !== '--dry-run' && $arg !== '-n';
        });
        $args = array_values($args);

        if ($dryRun) {
            echo "试运行模式：将显示要更新的内容但不会实际修改配置文件\n\n";
        }

        if (empty($args)) {
            return $this->updateAll($dryRun);
        }

        $type = $args[0];
        $target = isset($args[1]) ? $args[1] : null;

        switch ($type) {
            case 'php':
                return $this->updatePhp($dryRun);

            case 'pecl':
                return $this->updatePecl($target, $dryRun);

            case 'github':
            case 'ext':
                return $this->updateGithub($target, $dryRun);

            default:
                echo "错误: 未知的更新类型 '$type'\n";
                echo "可用类型: php, pecl, github/ext\n";
                echo "\n使用示例:\n";
                echo "  ./bin/pvm-mirror update-config                    # 更新所有版本配置\n";
                echo "  ./bin/pvm-mirror update-config --dry-run          # 试运行模式\n";
                echo "  ./bin/pvm-mirror update-config php                # 更新PHP版本配置\n";
                echo "  ./bin/pvm-mirror update-config pecl               # 更新所有PECL扩展版本配置\n";
                echo "  ./bin/pvm-mirror update-config pecl redis         # 更新指定PECL扩展版本配置\n";
                echo "  ./bin/pvm-mirror update-config github             # 更新所有GitHub扩展版本配置\n";
                echo "  ./bin/pvm-mirror update-config ext swoole         # 更新指定GitHub扩展版本配置\n";
                return 1;
        }
    }

    /**
     * 更新所有版本配置
     *
     * @param bool $dryRun 是否为试运行
     * @return int 退出代码
     */
    private function updateAll($dryRun)
    {
        echo "开始更新所有版本配置...\n\n";

        $success = $this->updateService->updateAllVersions($dryRun);
        
        if ($success) {
            if ($dryRun) {
                echo "\n试运行完成，使用不带 --dry-run 参数的命令来实际更新配置\n";
            } else {
                echo "\n所有版本配置更新完成\n";
            }
            return 0;
        } else {
            echo "\n配置更新失败\n";
            return 1;
        }
    }

    /**
     * 更新PHP版本配置
     *
     * @param bool $dryRun 是否为试运行
     * @return int 退出代码
     */
    private function updatePhp($dryRun)
    {
        echo "开始更新 PHP 版本配置...\n\n";

        $success = $this->updateService->updatePhpVersions($dryRun);
        
        if ($success) {
            if ($dryRun) {
                echo "\n试运行完成\n";
            } else {
                echo "\nPHP 版本配置更新完成\n";
            }
            return 0;
        } else {
            echo "\nPHP 版本配置更新失败\n";
            return 1;
        }
    }

    /**
     * 更新PECL扩展版本配置
     *
     * @param string|null $extensionName 扩展名
     * @param bool $dryRun 是否为试运行
     * @return int 退出代码
     */
    private function updatePecl($extensionName, $dryRun)
    {
        if ($extensionName) {
            echo "开始更新 PECL 扩展 '$extensionName' 的版本配置...\n\n";
        } else {
            echo "开始更新所有 PECL 扩展版本配置...\n\n";
        }

        $success = $this->updateService->updatePeclVersions($extensionName, $dryRun);
        
        if ($success) {
            if ($dryRun) {
                echo "\n试运行完成\n";
            } else {
                echo "\nPECL 扩展版本配置更新完成\n";
            }
            return 0;
        } else {
            echo "\nPECL 扩展版本配置更新失败\n";
            return 1;
        }
    }

    /**
     * 更新GitHub扩展版本配置
     *
     * @param string|null $extensionName 扩展名
     * @param bool $dryRun 是否为试运行
     * @return int 退出代码
     */
    private function updateGithub($extensionName, $dryRun)
    {
        if ($extensionName) {
            echo "开始更新 GitHub 扩展 '$extensionName' 的版本配置...\n\n";
        } else {
            echo "开始更新所有 GitHub 扩展版本配置...\n\n";
        }

        $success = $this->updateService->updateGithubVersions($extensionName, $dryRun);
        
        if ($success) {
            if ($dryRun) {
                echo "\n试运行完成\n";
            } else {
                echo "\nGitHub 扩展版本配置更新完成\n";
            }
            return 0;
        } else {
            echo "\nGitHub 扩展版本配置更新失败\n";
            return 1;
        }
    }

    /**
     * 获取命令帮助信息
     *
     * @return string 帮助信息
     */
    public function getHelp()
    {
        return "配置更新命令\n\n" .
               "用法:\n" .
               "  update-config [选项] [类型] [目标]\n\n" .
               "选项:\n" .
               "  --dry-run, -n    试运行模式，不实际修改配置文件\n\n" .
               "参数:\n" .
               "  类型    更新类型: php, pecl, github/ext\n" .
               "  目标    指定目标（仅对 pecl 和 github 有效）\n\n" .
               "示例:\n" .
               "  update-config                    更新所有版本配置\n" .
               "  update-config --dry-run          试运行模式\n" .
               "  update-config php                更新PHP版本配置\n" .
               "  update-config pecl               更新所有PECL扩展版本配置\n" .
               "  update-config pecl redis         更新指定PECL扩展版本配置\n" .
               "  update-config github             更新所有GitHub扩展版本配置\n" .
               "  update-config ext swoole         更新指定GitHub扩展版本配置\n";
    }
}
