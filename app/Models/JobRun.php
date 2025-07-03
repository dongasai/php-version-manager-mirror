<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 队列任务执行记录模型
 *
 * @property int $id
 * @property int|null $job_id 关联的队列任务ID
 * @property string $job_class 任务类名
 * @property string $queue 队列名称
 * @property string $status 执行状态
 * @property array|null $payload 任务载荷数据
 * @property string|null $output 执行输出
 * @property string|null $error 错误信息
 * @property int|null $memory_usage 内存使用量(字节)
 * @property float|null $execution_time 执行时间(秒)
 * @property \Carbon\Carbon|null $started_at 开始时间
 * @property \Carbon\Carbon|null $completed_at 完成时间
 * @property \Carbon\Carbon|null $created_at 创建时间
 * @property \Carbon\Carbon|null $updated_at 更新时间
 */
class JobRun extends Model
{
    use HasFactory;

    /**
     * 表名
     *
     * @var string
     */
    protected $table = 'job_runs';

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'job_id',
        'job_class',
        'queue',
        'status',
        'payload',
        'output',
        'error',
        'memory_usage',
        'execution_time',
        'started_at',
        'completed_at',
    ];

    /**
     * 属性类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'memory_usage' => 'integer',
        'execution_time' => 'decimal:3',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * 状态常量
     */
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_TIMEOUT = 'timeout';

    /**
     * 获取所有状态选项
     *
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_RUNNING => '运行中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_FAILED => '失败',
            self::STATUS_TIMEOUT => '超时',
        ];
    }

    /**
     * 获取状态标签
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    /**
     * 获取格式化的内存使用量
     *
     * @return string|null
     */
    public function getFormattedMemoryUsageAttribute(): ?string
    {
        if (!$this->memory_usage) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->memory_usage;
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * 获取执行持续时间
     *
     * @return float|null
     */
    public function getDurationAttribute(): ?float
    {
        if (!$this->started_at || !$this->completed_at) {
            return null;
        }

        return $this->completed_at->diffInSeconds($this->started_at, true);
    }

    /**
     * 关联到队列任务
     *
     * @return BelongsTo
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    /**
     * 作用域：按状态筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 作用域：按任务类筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $jobClass
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByJobClass($query, string $jobClass)
    {
        return $query->where('job_class', $jobClass);
    }

    /**
     * 作用域：按队列筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $queue
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByQueue($query, string $queue)
    {
        return $query->where('queue', $queue);
    }

    /**
     * 作用域：最近的记录
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
