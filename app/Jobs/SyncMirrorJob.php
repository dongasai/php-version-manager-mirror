<?php

namespace App\Jobs;

use App\Models\JobRun;
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
     * 任务执行记录
     *
     * @var JobRun|null
     */
    protected $jobRun;

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
        // 创建任务执行记录
        $this->jobRun = $this->createJobRun();

        Log::info("开始执行镜像同步任务", [
            'job_id' => $this->syncJob->id,
            'job_run_id' => $this->jobRun->id,
            'mirror_type' => $this->syncJob->mirror_type
        ]);

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        try {
            $result = $syncService->executeSyncJob($this->syncJob);

            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);

            if ($result) {
                Log::info("镜像同步任务完成", [
                    'job_id' => $this->syncJob->id,
                    'job_run_id' => $this->jobRun->id
                ]);

                $this->completeJobRun($startTime, $endTime, $startMemory, $endMemory, '任务执行成功');
            } else {
                Log::error("镜像同步任务失败", [
                    'job_id' => $this->syncJob->id,
                    'job_run_id' => $this->jobRun->id
                ]);

                $this->failJobRun($startTime, microtime(true), $startMemory, memory_get_usage(true), '同步任务执行失败');
                $this->fail(new \Exception("同步任务执行失败"));
            }
        } catch (\Exception $e) {
            Log::error("镜像同步任务异常", [
                'job_id' => $this->syncJob->id,
                'job_run_id' => $this->jobRun->id,
                'error' => $e->getMessage()
            ]);

            $this->failJobRun($startTime, microtime(true), $startMemory, memory_get_usage(true), $e->getMessage());
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
            'job_run_id' => $this->jobRun?->id,
            'error' => $exception->getMessage()
        ]);

        // 更新同步任务状态
        $this->syncJob->update([
            'status' => 'failed',
            'log' => $this->syncJob->log . "\n任务失败: " . $exception->getMessage(),
        ]);

        // 如果 jobRun 还没有被标记为失败，则更新状态
        if ($this->jobRun && $this->jobRun->status === JobRun::STATUS_RUNNING) {
            $this->jobRun->update([
                'status' => JobRun::STATUS_FAILED,
                'error' => $exception->getMessage(),
                'completed_at' => now(),
            ]);
        }
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

    /**
     * 创建任务执行记录
     *
     * @return JobRun
     */
    protected function createJobRun(): JobRun
    {
        return JobRun::create([
            'job_id' => $this->job?->getJobId(),
            'job_class' => static::class,
            'queue' => $this->queue ?? 'default',
            'status' => JobRun::STATUS_RUNNING,
            'payload' => [
                'sync_job_id' => $this->syncJob->id,
                'mirror_type' => $this->syncJob->mirror_type,
            ],
            'started_at' => now(),
        ]);
    }

    /**
     * 完成任务执行记录
     *
     * @param float $startTime
     * @param float $endTime
     * @param int $startMemory
     * @param int $endMemory
     * @param string $output
     * @return void
     */
    protected function completeJobRun(float $startTime, float $endTime, int $startMemory, int $endMemory, string $output): void
    {
        $this->jobRun->update([
            'status' => JobRun::STATUS_COMPLETED,
            'output' => $output,
            'memory_usage' => $endMemory - $startMemory,
            'execution_time' => $endTime - $startTime,
            'completed_at' => now(),
        ]);
    }

    /**
     * 标记任务执行记录为失败
     *
     * @param float $startTime
     * @param float $endTime
     * @param int $startMemory
     * @param int $endMemory
     * @param string $error
     * @return void
     */
    protected function failJobRun(float $startTime, float $endTime, int $startMemory, int $endMemory, string $error): void
    {
        $this->jobRun->update([
            'status' => JobRun::STATUS_FAILED,
            'error' => $error,
            'memory_usage' => $endMemory - $startMemory,
            'execution_time' => $endTime - $startTime,
            'completed_at' => now(),
        ]);
    }
}
