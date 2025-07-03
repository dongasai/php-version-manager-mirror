<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Job;
use App\Models\FailedJob;
use App\Models\SyncJob;
use Carbon\Carbon;

class QueueTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 创建一些队列任务测试数据
        $this->createQueueJobs();
        
        // 创建一些失败任务测试数据
        $this->createFailedJobs();
        
        // 创建一些同步任务测试数据
        $this->createSyncJobs();
    }

    /**
     * 创建队列任务测试数据
     */
    protected function createQueueJobs()
    {
        $jobs = [
            [
                'queue' => 'default',
                'payload' => json_encode([
                    'displayName' => 'App\\Jobs\\SyncMirrorJob',
                    'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                    'data' => ['mirror_type' => 'php']
                ]),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => time(),
                'created_at' => time(),
            ],
            [
                'queue' => 'default',
                'payload' => json_encode([
                    'displayName' => 'App\\Jobs\\SyncMirrorJob',
                    'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                    'data' => ['mirror_type' => 'pecl']
                ]),
                'attempts' => 1,
                'reserved_at' => time(),
                'available_at' => time() - 300,
                'created_at' => time() - 300,
            ],
            [
                'queue' => 'sync',
                'payload' => json_encode([
                    'displayName' => 'App\\Jobs\\SyncMirrorJob',
                    'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                    'data' => ['mirror_type' => 'composer']
                ]),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => time() + 3600,
                'created_at' => time(),
            ],
        ];

        foreach ($jobs as $job) {
            Job::create($job);
        }
    }

    /**
     * 创建失败任务测试数据
     */
    protected function createFailedJobs()
    {
        $failedJobs = [
            [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'connection' => 'database',
                'queue' => 'default',
                'payload' => json_encode([
                    'displayName' => 'App\\Jobs\\SyncMirrorJob',
                    'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                    'data' => ['mirror_type' => 'php']
                ]),
                'exception' => 'Exception: Network timeout while downloading PHP source files
Stack trace:
#0 /var/www/html/app/Services/PhpMirrorService.php(45): App\\Services\\PhpMirrorService->downloadFile()
#1 /var/www/html/app/Jobs/SyncMirrorJob.php(28): App\\Services\\PhpMirrorService->sync()
#2 [internal function]: App\\Jobs\\SyncMirrorJob->handle()',
                'failed_at' => now()->subHours(2),
            ],
            [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'connection' => 'database',
                'queue' => 'default',
                'payload' => json_encode([
                    'displayName' => 'App\\Jobs\\SyncMirrorJob',
                    'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                    'data' => ['mirror_type' => 'pecl']
                ]),
                'exception' => 'Exception: PECL API rate limit exceeded
Stack trace:
#0 /var/www/html/app/Services/PeclMirrorService.php(67): App\\Services\\PeclMirrorService->fetchExtensionInfo()
#1 /var/www/html/app/Jobs/SyncMirrorJob.php(28): App\\Services\\PeclMirrorService->sync()
#2 [internal function]: App\\Jobs\\SyncMirrorJob->handle()',
                'failed_at' => now()->subHour(),
            ],
            [
                'uuid' => \Illuminate\Support\Str::uuid(),
                'connection' => 'database',
                'queue' => 'sync',
                'payload' => json_encode([
                    'displayName' => 'App\\Jobs\\SyncMirrorJob',
                    'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                    'data' => ['mirror_type' => 'github']
                ]),
                'exception' => 'Exception: GitHub API authentication failed
Stack trace:
#0 /var/www/html/app/Services/ExtensionMirrorService.php(89): App\\Services\\ExtensionMirrorService->fetchReleases()
#1 /var/www/html/app/Jobs/SyncMirrorJob.php(28): App\\Services\\ExtensionMirrorService->sync()
#2 [internal function]: App\\Jobs\\SyncMirrorJob->handle()',
                'failed_at' => now()->subMinutes(30),
            ],
        ];

        foreach ($failedJobs as $job) {
            FailedJob::create($job);
        }
    }

    /**
     * 创建同步任务测试数据
     */
    protected function createSyncJobs()
    {
        $syncJobs = [
            [
                'mirror_type' => 'php',
                'status' => 'completed',
                'progress' => 100,
                'log' => "同步PHP源码包完成\n下载了15个版本\n总大小: 245MB",
                'started_at' => now()->subHours(3),
                'completed_at' => now()->subHours(2),
            ],
            [
                'mirror_type' => 'pecl',
                'status' => 'running',
                'progress' => 65,
                'log' => "正在同步PECL扩展包\n已处理: redis, memcached, mongodb\n当前: imagick",
                'started_at' => now()->subMinutes(45),
                'completed_at' => null,
            ],
            [
                'mirror_type' => 'composer',
                'status' => 'failed',
                'progress' => 25,
                'log' => "同步Composer包失败\n错误: 网络连接超时\n已重试3次",
                'started_at' => now()->subHour(),
                'completed_at' => now()->subMinutes(30),
            ],
            [
                'mirror_type' => 'github',
                'status' => 'pending',
                'progress' => 0,
                'log' => null,
                'started_at' => null,
                'completed_at' => null,
            ],
        ];

        foreach ($syncJobs as $job) {
            SyncJob::create($job);
        }
    }
}
