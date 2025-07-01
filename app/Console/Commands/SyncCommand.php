<?php

namespace App\Console\Commands;

use App\Services\MirrorService;
use Illuminate\Console\Command;

class SyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mirror:sync
                            {type? : 镜像类型 (php, pecl, extension, composer)}
                            {version? : 指定版本 (仅对php有效)}
                            {--force : 强制同步}
                            {--all : 同步所有镜像}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步镜像内容';

    /**
     * 镜像服务
     *
     * @var MirrorService
     */
    protected $mirrorService;

    /**
     * 构造函数
     *
     * @param MirrorService $mirrorService
     */
    public function __construct(MirrorService $mirrorService)
    {
        parent::__construct();
        $this->mirrorService = $mirrorService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = $this->argument('type');
        $version = $this->argument('version');
        $force = $this->option('force');
        $all = $this->option('all');

        if ($all) {
            return $this->syncAllMirrors($force);
        }

        if (!$type) {
            $this->error('请指定镜像类型或使用 --all 选项');
            $this->line('可用类型: php, pecl, extension, composer');
            $this->line('使用示例:');
            $this->line('  php artisan mirror:sync php');
            $this->line('  php artisan mirror:sync php 8.3');
            $this->line('  php artisan mirror:sync --all');
            return 1;
        }

        return $this->syncSpecificMirror($type, $version, $force);
    }

    /**
     * 同步所有镜像
     *
     * @param bool $force 是否强制同步
     * @return int
     */
    protected function syncAllMirrors(bool $force): int
    {
        $this->info('开始同步所有镜像...');

        try {
            $jobs = $this->mirrorService->syncAllMirrors($force);

            $this->info("已创建 " . count($jobs) . " 个同步任务");

            foreach ($jobs as $job) {
                $this->line("  任务 #{$job->id}: {$job->mirror->name} ({$job->mirror->type})");
            }

            $this->info('所有同步任务已提交到队列');
            return 0;

        } catch (\Exception $e) {
            $this->error("同步失败: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * 同步指定镜像
     *
     * @param string $type 镜像类型
     * @param string|null $version 版本号
     * @param bool $force 是否强制同步
     * @return int
     */
    protected function syncSpecificMirror(string $type, ?string $version, bool $force): int
    {
        $this->info("开始同步 {$type} 镜像" . ($version ? " (版本: {$version})" : ''));

        try {
            $mirrors = $this->mirrorService->getMirrorsByType($type);

            if ($mirrors->isEmpty()) {
                $this->error("未找到类型为 {$type} 的镜像配置");
                return 1;
            }

            $jobs = [];
            foreach ($mirrors as $mirror) {
                $job = $this->mirrorService->syncMirror($mirror->id, $force);
                $jobs[] = $job;

                $this->line("  已创建任务 #{$job->id}: {$mirror->name}");
            }

            $this->info("已创建 " . count($jobs) . " 个同步任务");
            $this->info('同步任务已提交到队列');
            return 0;

        } catch (\Exception $e) {
            $this->error("同步失败: " . $e->getMessage());
            return 1;
        }
    }
}
