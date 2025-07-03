<?php

namespace App\Console\Commands;

use App\Models\Mirror;
use App\Models\SyncJob;
use App\Services\MirrorService;
use App\Services\ConfigService;
use App\Services\MirrorConfigService;
use Illuminate\Console\Command;

class StatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mirror:status
                            {type? : 镜像类型 (php, pecl, extension, composer)}
                            {--jobs : 显示同步任务状态}
                            {--detailed : 显示详细信息}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '查看镜像状态';

    /**
     * 镜像服务
     *
     * @var MirrorService
     */
    protected $mirrorService;

    /**
     * 配置服务
     *
     * @var ConfigService
     */
    protected $configService;

    /**
     * 镜像配置服务
     *
     * @var MirrorConfigService
     */
    protected $mirrorConfigService;

    /**
     * 构造函数
     *
     * @param MirrorService $mirrorService
     * @param ConfigService $configService
     * @param MirrorConfigService $mirrorConfigService
     */
    public function __construct(MirrorService $mirrorService, ConfigService $configService, MirrorConfigService $mirrorConfigService)
    {
        parent::__construct();
        $this->mirrorService = $mirrorService;
        $this->configService = $configService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $showJobs = $this->option('jobs');
        $detailed = $this->option('detailed');

        if ($showJobs) {
            return $this->showSyncJobs($type);
        }

        if ($type) {
            return $this->showMirrorStatus($type, $detailed);
        }

        return $this->showOverallStatus($detailed);
    }

    /**
     * 显示整体状态
     *
     * @param bool $detailed 是否显示详细信息
     * @return int
     */
    protected function showOverallStatus(bool $detailed): int
    {
        $this->info('=== PVM 镜像状态概览 ===');

        // 系统信息
        $this->line('');
        $this->info('系统信息:');
        $dataDir = $this->configService->getDataDir();
        $this->line("  数据目录: {$dataDir}");
        $this->line("  缓存目录: " . $this->configService->getCacheDir());

        // 镜像统计
        $this->line('');
        $this->info('镜像统计:');
        $mirrors = Mirror::all();
        $enabledMirrors = $mirrors->where('status', 1);

        $this->line("  总镜像数: " . $mirrors->count());
        $this->line("  启用镜像: " . $enabledMirrors->count());
        $this->line("  禁用镜像: " . $mirrors->where('status', 0)->count());

        // 按类型统计
        $typeStats = $enabledMirrors->groupBy('type');
        foreach ($typeStats as $type => $typeMirrors) {
            $this->line("    {$type}: " . $typeMirrors->count() . " 个");
        }

        // 同步任务统计
        $this->line('');
        $this->info('同步任务统计:');
        $totalJobs = SyncJob::count();
        $runningJobs = SyncJob::running()->count();
        $completedJobs = SyncJob::completed()->count();
        $failedJobs = SyncJob::failed()->count();

        $this->line("  总任务数: {$totalJobs}");
        $this->line("  运行中: {$runningJobs}");
        $this->line("  已完成: {$completedJobs}");
        $this->line("  失败: {$failedJobs}");

        if ($detailed) {
            $this->showDetailedMirrorInfo();
        }

        return 0;
    }

    /**
     * 显示指定类型镜像状态
     *
     * @param string $type 镜像类型
     * @param bool $detailed 是否显示详细信息
     * @return int
     */
    protected function showMirrorStatus(string $type, bool $detailed): int
    {
        $this->info("=== {$type} 镜像状态 ===");

        $mirrors = Mirror::where('type', $type)->get();

        if ($mirrors->isEmpty()) {
            $this->error("未找到类型为 {$type} 的镜像");
            return 1;
        }

        foreach ($mirrors as $mirror) {
            $status = $this->mirrorService->getMirrorStatus($mirror->id);

            $this->line('');
            $this->info("镜像: {$mirror->name}");
            $this->line("  状态: " . ($mirror->isEnabled() ? '启用' : '禁用'));
            $this->line("  URL: {$mirror->url}");

            if ($status['stats']) {
                $stats = $status['stats'];
                $this->line("  文件数: {$stats['file_count']}");
                $this->line("  总大小: " . $this->formatBytes($stats['total_size']));
                if ($stats['last_updated']) {
                    $this->line("  最后更新: {$stats['last_updated']}");
                }
            }

            if ($status['latest_job']) {
                $job = $status['latest_job'];
                $this->line("  最新任务: #{$job->id} ({$job->status_name})");
                if ($job->started_at) {
                    $this->line("  开始时间: {$job->started_at}");
                }
                if ($job->completed_at) {
                    $this->line("  完成时间: {$job->completed_at}");
                }
                if ($job->duration) {
                    $this->line("  执行时长: {$job->duration}");
                }
            }

            if ($detailed && $mirror->config) {
                $this->line("  配置:");
                foreach ($mirror->config as $key => $value) {
                    if (is_array($value)) {
                        $this->line("    {$key}: " . json_encode($value, JSON_UNESCAPED_UNICODE));
                    } else {
                        $this->line("    {$key}: {$value}");
                    }
                }
            }
        }

        return 0;
    }

    /**
     * 显示同步任务状态
     *
     * @param string|null $type 镜像类型
     * @return int
     */
    protected function showSyncJobs(?string $type): int
    {
        $this->info('=== 同步任务状态 ===');

        $query = SyncJob::with('mirror')->orderBy('created_at', 'desc');

        if ($type) {
            $query->whereHas('mirror', function ($q) use ($type) {
                $q->where('type', $type);
            });
        }

        $jobs = $query->limit(20)->get();

        if ($jobs->isEmpty()) {
            $this->line('暂无同步任务');
            return 0;
        }

        $headers = ['ID', '镜像', '类型', '状态', '进度', '开始时间', '完成时间', '时长'];
        $rows = [];

        foreach ($jobs as $job) {
            $rows[] = [
                $job->id,
                $job->mirror->name,
                $job->mirror->type,
                $job->status_name,
                $job->progress_percent,
                $job->started_at ? $job->started_at->format('m-d H:i') : '-',
                $job->completed_at ? $job->completed_at->format('m-d H:i') : '-',
                $job->duration ?? '-',
            ];
        }

        $this->table($headers, $rows);

        return 0;
    }

    /**
     * 显示详细镜像信息
     *
     * @return void
     */
    protected function showDetailedMirrorInfo(): void
    {
        $this->line('');
        $this->info('详细镜像信息:');

        $mirrors = Mirror::enabled()->get();

        foreach ($mirrors as $mirror) {
            $stats = $this->mirrorService->getMirrorStats($mirror);

            $this->line('');
            $this->line("  {$mirror->name} ({$mirror->type}):");
            $this->line("    文件数: {$stats['file_count']}");
            $this->line("    大小: " . $this->formatBytes($stats['total_size']));
            if ($stats['last_updated']) {
                $this->line("    更新: {$stats['last_updated']}");
            }
        }
    }

    /**
     * 格式化字节大小
     *
     * @param int $bytes 字节数
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
