<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use App\Services\ConfigService;
use App\Models\FileCache;
use App\Models\AccessLog;
use App\Models\SyncJob;
use Illuminate\Console\Command;

class CleanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mirror:clean
                            {type? : 清理类型 (cache, logs, jobs, all)}
                            {--days=30 : 保留天数}
                            {--force : 强制清理，不询问确认}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '清理缓存和日志';

    /**
     * 缓存服务
     *
     * @var CacheService
     */
    protected $cacheService;

    /**
     * 配置服务
     *
     * @var ConfigService
     */
    protected $configService;

    /**
     * 构造函数
     *
     * @param CacheService $cacheService
     * @param ConfigService $configService
     */
    public function __construct(CacheService $cacheService, ConfigService $configService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->configService = $configService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type') ?? 'all';
        $days = (int) $this->option('days');
        $force = $this->option('force');

        if (!$force) {
            if (!$this->confirm("确定要清理 {$type} 吗？(保留 {$days} 天内的数据)")) {
                $this->info('清理操作已取消');
                return 0;
            }
        }

        return match ($type) {
            'cache' => $this->cleanCache($days),
            'logs' => $this->cleanLogs($days),
            'jobs' => $this->cleanJobs($days),
            'all' => $this->cleanAll($days),
            default => $this->showUsage()
        };
    }

    /**
     * 清理缓存
     *
     * @param int $days 保留天数
     * @return int
     */
    protected function cleanCache(int $days): int
    {
        $this->info('开始清理缓存...');

        try {
            // 清理过期文件缓存
            $cleanedFiles = $this->cacheService->cleanExpiredFileCache($days);
            $this->line("清理过期文件缓存: {$cleanedFiles} 条记录");

            // 清理缓存目录
            $this->cacheService->cleanCacheDirectory(1024); // 限制1GB
            $this->line("清理缓存目录完成");

            // 清理内存缓存
            $this->cacheService->clearAll();
            $this->line("清理内存缓存完成");

            $this->info('缓存清理完成');
            return 0;

        } catch (\Exception $e) {
            $this->error("缓存清理失败: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 清理日志
     *
     * @param int $days 保留天数
     * @return int
     */
    protected function cleanLogs(int $days): int
    {
        $this->info('开始清理日志...');

        try {
            // 清理访问日志
            $cleanedLogs = AccessLog::cleanupOldLogs($days);
            $this->line("清理访问日志: {$cleanedLogs} 条记录");

            // 清理Laravel日志文件
            $this->cleanLaravelLogs($days);
            $this->line("清理Laravel日志文件完成");

            $this->info('日志清理完成');
            return 0;

        } catch (\Exception $e) {
            $this->error("日志清理失败: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 清理同步任务
     *
     * @param int $days 保留天数
     * @return int
     */
    protected function cleanJobs(int $days): int
    {
        $this->info('开始清理同步任务...');

        try {
            $expiredDate = now()->subDays($days);

            // 只清理已完成和失败的任务
            $cleanedJobs = SyncJob::whereIn('status', ['completed', 'failed'])
                                 ->where('updated_at', '<', $expiredDate)
                                 ->delete();

            $this->line("清理同步任务: {$cleanedJobs} 条记录");
            $this->info('同步任务清理完成');
            return 0;

        } catch (\Exception $e) {
            $this->error("同步任务清理失败: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 清理所有
     *
     * @param int $days 保留天数
     * @return int
     */
    protected function cleanAll(int $days): int
    {
        $this->info('开始全面清理...');

        $results = [
            'cache' => $this->cleanCache($days),
            'logs' => $this->cleanLogs($days),
            'jobs' => $this->cleanJobs($days),
        ];

        $failed = array_filter($results, fn($result) => $result !== 0);

        if (empty($failed)) {
            $this->info('全面清理完成');
            return 0;
        } else {
            $this->error('部分清理操作失败');
            return 1;
        }
    }

    /**
     * 清理Laravel日志文件
     *
     * @param int $days 保留天数
     * @return void
     */
    protected function cleanLaravelLogs(int $days): void
    {
        $logPath = storage_path('logs');
        $expiredTime = time() - ($days * 24 * 3600);

        if (!is_dir($logPath)) {
            return;
        }

        $files = glob($logPath . '/*.log');
        $cleanedCount = 0;

        foreach ($files as $file) {
            if (filemtime($file) < $expiredTime) {
                if (unlink($file)) {
                    $cleanedCount++;
                }
            }
        }

        if ($cleanedCount > 0) {
            $this->line("清理Laravel日志文件: {$cleanedCount} 个文件");
        }
    }

    /**
     * 显示使用说明
     *
     * @return int
     */
    protected function showUsage(): int
    {
        $this->error('无效的清理类型');
        $this->line('可用类型:');
        $this->line('  cache - 清理缓存');
        $this->line('  logs  - 清理日志');
        $this->line('  jobs  - 清理同步任务');
        $this->line('  all   - 清理所有');
        $this->line('');
        $this->line('使用示例:');
        $this->line('  php artisan mirror:clean cache');
        $this->line('  php artisan mirror:clean logs --days=7');
        $this->line('  php artisan mirror:clean all --force');

        return 1;
    }
}
