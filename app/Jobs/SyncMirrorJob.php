<?php

namespace App\Jobs;

use App\Models\SyncJob;
use App\Services\SyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 镜像同步队列任务
 */
class SyncMirrorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 同步任务
     *
     * @var SyncJob
     */
    protected $syncJob;

    /**
     * 任务超时时间（秒）
     *
     * @var int
     */
    public $timeout = 3600; // 1小时

    /**
     * 最大重试次数
     *
     * @var int
     */
    public $tries = 3;

    /**
     * 创建新的任务实例
     *
     * @param SyncJob $syncJob
     */
    public function __construct(SyncJob $syncJob)
    {
        $this->syncJob = $syncJob;
    }

    /**
     * 执行任务
     *
     * @param SyncService $syncService
     * @return void
     */
    public function handle(SyncService $syncService)
    {
        Log::info("开始执行镜像同步任务", [
            'job_id' => $this->syncJob->id,
            'mirror_type' => $this->syncJob->mirror_type
        ]);

        try {
            $result = $syncService->executeSyncJob($this->syncJob);
            
            if ($result) {
                Log::info("镜像同步任务完成", [
                    'job_id' => $this->syncJob->id
                ]);
            } else {
                Log::error("镜像同步任务失败", [
                    'job_id' => $this->syncJob->id
                ]);
                
                $this->fail(new \Exception("同步任务执行失败"));
            }
        } catch (\Exception $e) {
            Log::error("镜像同步任务异常", [
                'job_id' => $this->syncJob->id,
                'error' => $e->getMessage()
            ]);
            
            $this->fail($e);
        }
    }

    /**
     * 任务失败处理
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error("镜像同步任务最终失败", [
            'job_id' => $this->syncJob->id,
            'error' => $exception->getMessage()
        ]);

        // 更新同步任务状态
        $this->syncJob->update([
            'status' => 'failed',
            'log' => $this->syncJob->log . "\n任务失败: " . $exception->getMessage(),
        ]);
    }

    /**
     * 获取任务的唯一ID
     *
     * @return string
     */
    public function uniqueId()
    {
        return 'sync_mirror_' . $this->syncJob->id;
    }
}
