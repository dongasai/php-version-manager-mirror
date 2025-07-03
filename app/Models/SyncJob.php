<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 同步任务模型
 */
class SyncJob extends Model
{
    use HasFactory;

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'mirror_type',
        'status',
        'progress',
        'log',
        'started_at',
        'completed_at',
    ];

    /**
     * 属性类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'progress' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * 状态常量
     */
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * 获取所有状态选项
     *
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => '等待中',
            self::STATUS_RUNNING => '运行中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_FAILED => '失败',
            self::STATUS_CANCELLED => '已取消',
        ];
    }

    /**
     * 获取状态名称
     *
     * @return string
     */
    public function getStatusNameAttribute(): string
    {
        $statuses = self::getStatusOptions();
        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * 获取状态颜色
     *
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_RUNNING => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_CANCELLED => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * 获取进度百分比
     *
     * @return string
     */
    public function getProgressPercentAttribute(): string
    {
        return $this->progress . '%';
    }

    /**
     * 获取执行时长
     *
     * @return string|null
     */
    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();
        $duration = $this->started_at->diffInSeconds($endTime);

        if ($duration < 60) {
            return $duration . '秒';
        } elseif ($duration < 3600) {
            return round($duration / 60, 1) . '分钟';
        } else {
            return round($duration / 3600, 1) . '小时';
        }
    }

    /**
     * 检查是否正在运行
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_RUNNING]);
    }

    /**
     * 检查是否已完成
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * 检查是否失败
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * 检查是否可以取消
     *
     * @return bool
     */
    public function canCancel(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_RUNNING]);
    }

    /**
     * 标记为开始
     *
     * @return bool
     */
    public function markAsStarted(): bool
    {
        return $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    /**
     * 标记为完成
     *
     * @return bool
     */
    public function markAsCompleted(): bool
    {
        return $this->update([
            'status' => self::STATUS_COMPLETED,
            'progress' => 100,
            'completed_at' => now(),
        ]);
    }

    /**
     * 标记为失败
     *
     * @param string|null $errorMessage
     * @return bool
     */
    public function markAsFailed(?string $errorMessage = null): bool
    {
        $data = [
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
        ];

        if ($errorMessage) {
            $data['log'] = $this->log . "\n错误: " . $errorMessage;
        }

        return $this->update($data);
    }

    /**
     * 标记为取消
     *
     * @return bool
     */
    public function markAsCancelled(): bool
    {
        return $this->update([
            'status' => self::STATUS_CANCELLED,
            'completed_at' => now(),
        ]);
    }

    /**
     * 更新进度
     *
     * @param int $progress
     * @return bool
     */
    public function updateProgress(int $progress): bool
    {
        return $this->update(['progress' => max(0, min(100, $progress))]);
    }

    /**
     * 添加日志
     *
     * @param string $message
     * @return bool
     */
    public function addLog(string $message): bool
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}";
        
        return $this->update([
            'log' => $this->log . "\n" . $logEntry
        ]);
    }

    /**
     * 获取镜像类型名称
     *
     * @return string
     */
    public function getMirrorTypeNameAttribute(): string
    {
        $types = [
            'php' => 'PHP源码',
            'pecl' => 'PECL扩展',
            'github' => 'GitHub扩展',
            'composer' => 'Composer包',
        ];

        return $types[$this->mirror_type] ?? $this->mirror_type;
    }

    /**
     * 获取同步任务统计信息
     *
     * @return array
     */
    public static function getSyncJobStats(): array
    {
        $total = static::count();
        $running = static::where('status', 'running')->count();
        $completed = static::where('status', 'completed')->count();
        $failed = static::where('status', 'failed')->count();
        $pending = static::where('status', 'pending')->count();

        return [
            'total' => $total,
            'running' => $running,
            'completed' => $completed,
            'failed' => $failed,
            'pending' => $pending,
        ];
    }

    /**
     * 作用域：正在运行的任务
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRunning($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_RUNNING]);
    }

    /**
     * 作用域：已完成的任务
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * 作用域：失败的任务
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * 作用域：按镜像筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $mirrorId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForMirror($query, int $mirrorId)
    {
        return $query->where('mirror_id', $mirrorId);
    }

    /**
     * 作用域：按镜像类型筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $mirrorType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForMirrorType($query, string $mirrorType)
    {
        return $query->where('mirror_type', $mirrorType);
    }



    /**
     * 清理过期任务
     *
     * @param int $days 保留天数
     * @return int
     */
    public static function cleanupExpiredJobs(int $days = 30): int
    {
        return self::where('status', self::STATUS_COMPLETED)
                  ->where('updated_at', '<', now()->subDays($days))
                  ->delete();
    }
}
