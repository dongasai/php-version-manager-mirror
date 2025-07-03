<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * 失败任务模型
 * 
 * 对应 failed_jobs 表，用于管理失败的队列任务
 */
class FailedJob extends Model
{
    /**
     * 数据表名
     *
     * @var string
     */
    protected $table = 'failed_jobs';

    /**
     * 主键字段
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * 是否使用时间戳
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'connection',
        'queue',
        'payload',
        'exception',
        'failed_at',
    ];

    /**
     * 属性类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'payload' => 'array',
        'failed_at' => 'datetime',
    ];

    /**
     * 获取任务类名
     *
     * @return string
     */
    public function getJobClassAttribute(): string
    {
        $payload = $this->payload;
        if (isset($payload['displayName'])) {
            return $payload['displayName'];
        }
        
        if (isset($payload['job'])) {
            $job = unserialize($payload['job']);
            return get_class($job);
        }
        
        return 'Unknown Job';
    }

    /**
     * 获取任务数据
     *
     * @return array
     */
    public function getJobDataAttribute(): array
    {
        $payload = $this->payload;
        if (isset($payload['data'])) {
            return $payload['data'];
        }
        
        return [];
    }

    /**
     * 获取异常摘要
     *
     * @return string
     */
    public function getExceptionSummaryAttribute(): string
    {
        $lines = explode("\n", $this->exception);
        return $lines[0] ?? 'Unknown Exception';
    }

    /**
     * 获取格式化的失败时间
     *
     * @return string
     */
    public function getFormattedFailedAtAttribute(): string
    {
        return $this->failed_at->format('Y-m-d H:i:s');
    }

    /**
     * 作用域：按队列名称筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $queue
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeQueue($query, string $queue)
    {
        return $query->where('queue', $queue);
    }

    /**
     * 作用域：按连接筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $connection
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeConnection($query, string $connection)
    {
        return $query->where('connection', $connection);
    }

    /**
     * 作用域：按任务类筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $jobClass
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeJobClass($query, string $jobClass)
    {
        return $query->whereRaw("JSON_EXTRACT(payload, '$.displayName') = ?", [$jobClass]);
    }

    /**
     * 获取所有队列名称
     *
     * @return array
     */
    public static function getQueueNames(): array
    {
        return static::distinct('queue')->pluck('queue')->toArray();
    }

    /**
     * 获取所有连接名称
     *
     * @return array
     */
    public static function getConnectionNames(): array
    {
        return static::distinct('connection')->pluck('connection')->toArray();
    }

    /**
     * 获取失败任务统计信息
     *
     * @return array
     */
    public static function getFailedJobStats(): array
    {
        $total = static::count();
        $today = static::whereDate('failed_at', today())->count();
        $thisWeek = static::whereBetween('failed_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $thisMonth = static::whereMonth('failed_at', now()->month)->count();
        
        return [
            'total' => $total,
            'today' => $today,
            'this_week' => $thisWeek,
            'this_month' => $thisMonth,
        ];
    }

    /**
     * 获取按日期分组的失败统计
     *
     * @param int $days
     * @return array
     */
    public static function getFailedJobsByDate(int $days = 7): array
    {
        return static::selectRaw('DATE(failed_at) as date, COUNT(*) as count')
            ->where('failed_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }

    /**
     * 获取按队列分组的失败统计
     *
     * @return array
     */
    public static function getFailedJobsByQueue(): array
    {
        return static::selectRaw('queue, COUNT(*) as count')
            ->groupBy('queue')
            ->orderByDesc('count')
            ->pluck('count', 'queue')
            ->toArray();
    }
}
