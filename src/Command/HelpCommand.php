<?php

namespace Mirror\Command;

use Mirror\Application;

/**
 * 帮助命令类
 */
class HelpCommand extends AbstractCommand
{
    /**
     * 应用程序实例
     *
     * @var Application
     */
    private $application;

    /**
     * 构造函数
     *
     * @param Application $application 应用程序实例
     */
    public function __construct(Application $application)
    {
        parent::__construct('help', '显示帮助信息');
        $this->application = $application;
    }

    /**
     * 执行命令
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    public function execute(array $args = [])
    {
        echo "PVM 镜像应用\n";
        echo "用法: pvm-mirror <命令> [选项]\n\n";
        echo "可用命令:\n";

        // 获取所有命令
        $commands = $this->application->getCommands();

        // 计算最长命令名称的长度
        $maxLength = 0;
        foreach ($commands as $command) {
            $length = strlen($command->getName());
            if ($length > $maxLength) {
                $maxLength = $length;
            }
        }

        // 显示命令列表
        foreach ($commands as $command) {
            $name = $command->getName();
            $description = $command->getDescription();
            
            // 格式化输出
            $padding = str_repeat(' ', $maxLength - strlen($name) + 2);
            echo "  $name$padding$description\n";
        }

        echo "\n使用 'pvm-mirror help <命令>' 查看特定命令的帮助信息\n";

        return 0;
    }
}
