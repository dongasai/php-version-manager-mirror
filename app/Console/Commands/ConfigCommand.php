<?php

namespace App\Console\Commands;

use App\Services\ConfigService;
use App\Models\SystemConfig;
use Illuminate\Console\Command;

class ConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mirror:config
                            {action : 操作类型 (get, set, list, init)}
                            {key? : 配置键名}
                            {value? : 配置值}
                            {--description= : 配置描述}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '管理系统配置';

    /**
     * 配置服务
     *
     * @var ConfigService
     */
    protected $configService;

    /**
     * 构造函数
     *
     * @param ConfigService $configService
     */
    public function __construct(ConfigService $configService)
    {
        parent::__construct();
        $this->configService = $configService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $key = $this->argument('key');
        $value = $this->argument('value');
        $description = $this->option('description');

        return match ($action) {
            'get' => $this->getConfig($key),
            'set' => $this->setConfig($key, $value, $description),
            'list' => $this->listConfigs(),
            'init' => $this->initConfigs(),
            default => $this->showUsage()
        };
    }

    /**
     * 获取配置
     *
     * @param string|null $key 配置键
     * @return int
     */
    protected function getConfig(?string $key): int
    {
        if (!$key) {
            $this->error('请指定配置键名');
            return 1;
        }

        $value = $this->configService->get($key);

        if ($value === null) {
            $this->error("配置 {$key} 不存在");
            return 1;
        }

        $this->info("配置键: {$key}");

        if (is_array($value) || is_object($value)) {
            $this->line("配置值: " . json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } else {
            $this->line("配置值: {$value}");
        }

        return 0;
    }

    /**
     * 设置配置
     *
     * @param string|null $key 配置键
     * @param string|null $value 配置值
     * @param string|null $description 配置描述
     * @return int
     */
    protected function setConfig(?string $key, ?string $value, ?string $description): int
    {
        if (!$key) {
            $this->error('请指定配置键名');
            return 1;
        }

        if ($value === null) {
            $this->error('请指定配置值');
            return 1;
        }

        // 尝试解析JSON值
        $parsedValue = $this->parseValue($value);

        try {
            $result = $this->configService->set($key, $parsedValue, $description);

            if ($result) {
                $this->info("配置 {$key} 设置成功");

                if (is_array($parsedValue) || is_object($parsedValue)) {
                    $this->line("新值: " . json_encode($parsedValue, JSON_UNESCAPED_UNICODE));
                } else {
                    $this->line("新值: {$parsedValue}");
                }

                return 0;
            } else {
                $this->error("配置 {$key} 设置失败");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("设置配置失败: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 列出所有配置
     *
     * @return int
     */
    protected function listConfigs(): int
    {
        $this->info('=== 系统配置列表 ===');

        $configs = $this->configService->getAllConfigs();

        if (empty($configs)) {
            $this->line('暂无配置');
            return 0;
        }

        // 按分组显示
        $groups = [];
        foreach ($configs as $key => $config) {
            $group = explode('.', $key)[0];
            $groups[$group][] = ['key' => $key, 'config' => $config];
        }

        foreach ($groups as $group => $groupConfigs) {
            $this->line('');
            $this->info("分组: {$group}");

            foreach ($groupConfigs as $item) {
                $key = $item['key'];
                $config = $item['config'];
                $value = $config['value'];

                $this->line("  {$key}:");

                if (is_array($value) || is_object($value)) {
                    $this->line("    值: " . json_encode($value, JSON_UNESCAPED_UNICODE));
                } else {
                    $this->line("    值: {$value}");
                }

                if ($config['description']) {
                    $this->line("    描述: {$config['description']}");
                }

                $this->line("    更新: {$config['updated_at']}");
            }
        }

        return 0;
    }

    /**
     * 初始化默认配置
     *
     * @return int
     */
    protected function initConfigs(): int
    {
        $this->info('初始化默认配置...');

        try {
            SystemConfig::initializeDefaults();
            $this->info('默认配置初始化完成');
            return 0;
        } catch (\Exception $e) {
            $this->error("初始化配置失败: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 解析配置值
     *
     * @param string $value 原始值
     * @return mixed
     */
    protected function parseValue(string $value)
    {
        // 尝试解析为JSON
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // 尝试解析为布尔值
        if (in_array(strtolower($value), ['true', 'false'])) {
            return strtolower($value) === 'true';
        }

        // 尝试解析为数字
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }

        // 返回原始字符串
        return $value;
    }

    /**
     * 显示使用说明
     *
     * @return int
     */
    protected function showUsage(): int
    {
        $this->error('无效的操作类型');
        $this->line('可用操作:');
        $this->line('  get  - 获取配置值');
        $this->line('  set  - 设置配置值');
        $this->line('  list - 列出所有配置');
        $this->line('  init - 初始化默认配置');
        $this->line('');
        $this->line('使用示例:');
        $this->line('  php artisan mirror:config get system.data_dir');
        $this->line('  php artisan mirror:config set system.data_dir /data/mirror');
        $this->line('  php artisan mirror:config set sync.interval 12 --description="同步间隔(小时)"');
        $this->line('  php artisan mirror:config list');
        $this->line('  php artisan mirror:config init');

        return 1;
    }
}
