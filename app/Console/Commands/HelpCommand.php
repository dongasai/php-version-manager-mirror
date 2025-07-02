<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class HelpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mirror:help
                            {cmd? : 显示特定命令的帮助信息}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '显示镜像命令的帮助信息';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $commandName = $this->argument('cmd');

        if ($commandName) {
            return $this->showSpecificCommandHelp($commandName);
        }

        return $this->showAllMirrorCommands();
    }

    /**
     * 显示所有镜像命令
     *
     * @return int
     */
    protected function showAllMirrorCommands(): int
    {
        $this->info('PHP Version Manager Mirror - Laravel 版本');
        $this->info('用法: php artisan <命令> [选项]');
        $this->newLine();

        $this->info('可用的镜像命令:');
        $this->newLine();

        // 获取所有镜像命令
        $mirrorCommands = $this->getMirrorCommands();

        if (empty($mirrorCommands)) {
            $this->warn('未找到任何镜像命令');
            return 1;
        }

        // 计算最长命令名称的长度
        $maxLength = 0;
        foreach ($mirrorCommands as $command) {
            $length = strlen($command['name']);
            if ($length > $maxLength) {
                $maxLength = $length;
            }
        }

        // 按类别分组显示命令
        $categories = [
            '核心功能' => ['mirror:sync', 'mirror:status', 'mirror:clean'],
            '配置管理' => ['mirror:config'],
            '版本管理' => ['mirror:discover', 'mirror:split-versions'],
            '帮助信息' => ['mirror:help']
        ];

        foreach ($categories as $category => $commandNames) {
            $this->info("  {$category}:");
            
            foreach ($commandNames as $commandName) {
                $command = $this->findCommand($mirrorCommands, $commandName);
                if ($command) {
                    $padding = str_repeat(' ', $maxLength - strlen($command['name']) + 2);
                    $this->line("    {$command['name']}{$padding}{$command['description']}");
                }
            }
            
            $this->newLine();
        }

        $this->info('使用 "php artisan mirror:help <命令>" 查看特定命令的详细帮助信息');
        $this->info('使用 "php artisan help <命令>" 查看Laravel标准帮助信息');
        
        return 0;
    }

    /**
     * 显示特定命令的帮助信息
     *
     * @param string $commandName
     * @return int
     */
    protected function showSpecificCommandHelp(string $commandName): int
    {
        // 确保命令名包含 mirror: 前缀
        if (!str_starts_with($commandName, 'mirror:')) {
            $commandName = 'mirror:' . $commandName;
        }

        // 获取所有镜像命令
        $mirrorCommands = $this->getMirrorCommands();
        $command = $this->findCommand($mirrorCommands, $commandName);

        if (!$command) {
            $this->error("未找到命令: {$commandName}");
            $this->line('使用 "php artisan mirror:help" 查看所有可用命令');
            return 1;
        }

        // 显示命令详细信息
        $this->info("命令: {$command['name']}");
        $this->info("描述: {$command['description']}");
        $this->newLine();

        // 显示具体的使用示例
        $this->showCommandExamples($commandName);

        // 调用Laravel内置的help命令显示详细参数
        $this->newLine();
        $this->info('详细参数信息:');
        $this->call('help', ['command_name' => $commandName]);

        return 0;
    }

    /**
     * 获取所有镜像命令
     *
     * @return array
     */
    protected function getMirrorCommands(): array
    {
        $commands = [];
        $allCommands = Artisan::all();

        foreach ($allCommands as $name => $command) {
            if (str_starts_with($name, 'mirror:')) {
                $commands[] = [
                    'name' => $name,
                    'description' => $command->getDescription()
                ];
            }
        }

        return $commands;
    }

    /**
     * 查找特定命令
     *
     * @param array $commands
     * @param string $commandName
     * @return array|null
     */
    protected function findCommand(array $commands, string $commandName): ?array
    {
        foreach ($commands as $command) {
            if ($command['name'] === $commandName) {
                return $command;
            }
        }

        return null;
    }

    /**
     * 显示命令使用示例
     *
     * @param string $commandName
     */
    protected function showCommandExamples(string $commandName): void
    {
        $examples = [
            'mirror:sync' => [
                'php artisan mirror:sync                   # 同步所有镜像',
                'php artisan mirror:sync php               # 仅同步PHP源码',
                'php artisan mirror:sync pecl              # 仅同步PECL扩展',
                'php artisan mirror:sync --force           # 强制同步',
            ],
            'mirror:status' => [
                'php artisan mirror:status                 # 显示所有镜像状态',
                'php artisan mirror:status --json          # JSON格式输出',
            ],
            'mirror:clean' => [
                'php artisan mirror:clean                  # 清理过期文件',
                'php artisan mirror:clean --dry-run        # 试运行模式',
                'php artisan mirror:clean --force          # 强制清理',
            ],
            'mirror:config' => [
                'php artisan mirror:config list            # 列出所有配置',
                'php artisan mirror:config get key         # 获取配置项',
                'php artisan mirror:config set key value   # 设置配置项',
                'php artisan mirror:config init            # 初始化默认配置',
            ],
            'mirror:discover' => [
                'php artisan mirror:discover               # 发现所有版本',
                'php artisan mirror:discover php           # 发现PHP版本',
                'php artisan mirror:discover pecl          # 发现PECL扩展版本',
                'php artisan mirror:discover --json        # JSON格式输出',
                'php artisan mirror:discover --save        # 保存发现的版本',
            ],
            'mirror:split-versions' => [
                'php artisan mirror:split-versions php     # 分割PHP版本配置',
                'php artisan mirror:split-versions --dry-run # 试运行模式',
                'php artisan mirror:split-versions --backup  # 分割前备份',
                'php artisan mirror:split-versions --force   # 强制覆盖',
            ],
            'mirror:help' => [
                'php artisan mirror:help                   # 显示所有镜像命令',
                'php artisan mirror:help sync              # 显示sync命令帮助',
                'php artisan mirror:help discover          # 显示discover命令帮助',
            ],
        ];

        if (isset($examples[$commandName])) {
            $this->info('使用示例:');
            foreach ($examples[$commandName] as $example) {
                $this->line("  {$example}");
            }
        }
    }
}
