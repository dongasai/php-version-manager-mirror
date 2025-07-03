<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * 队列任务模型
 * 
 * 对应 jobs 表，用于管理Laravel队列任务
 */
class Job extends Model
{
    /**
     * 数据表名
     *
     * @var string
     */
    protected $table = 'jobs';

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
        'queue',
        'payload',
        'attempts',
        'reserved_at',
        'available_at',
        'created_at',
    ];

    /**
     * 属性类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'attempts' => 'integer',
        'reserved_at' => 'integer',
        'available_at' => 'integer',
        'created_at' => 'integer',
    ];

    /**
     * 获取解析后的payload
     *
     * @return array
     */
    public function getParsedPayloadAttribute(): array
    {
        $payload = json_decode($this->payload, true);

        // 如果第一次解码后还是字符串，说明被双重编码了
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
        }

        return $payload ?: [];
    }

    /**
     * 获取任务类名
     *
     * @return string
     */
    public function getJobClassAttribute(): string
    {
        $payload = $this->parsed_payload;
        if (isset($payload['displayName'])) {
            $parts = explode('\\', $payload['displayName']);
            return end($parts);
        }

        if (isset($payload['job'])) {
            $parts = explode('\\', $payload['job']);
            return end($parts);
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
        $payload = $this->parsed_payload;
        if (isset($payload['data'])) {
            return $payload['data'];
        }

        return [];
    }

    /**
     * 获取格式化的创建时间
     *
     * @return string
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        if (!$this->created_at) {
            return '';
        }
        return Carbon::createFromTimestamp($this->created_at)->format('Y-m-d H:i:s');
    }

    /**
     * 获取格式化的可用时间
     *
     * @return string
     */
    public function getFormattedAvailableAtAttribute(): string
    {
        if (!$this->available_at) {
            return '';
        }
        return Carbon::createFromTimestamp($this->available_at)->format('Y-m-d H:i:s');
    }

    /**
     * 获取格式化的保留时间
     *
     * @return string|null
     */
    public function getFormattedReservedAtAttribute(): ?string
    {
        if (!$this->reserved_at) {
            return null;
        }

        return Carbon::createFromTimestamp($this->reserved_at)->format('Y-m-d H:i:s');
    }

    /**
     * 获取任务状态
     *
     * @return string
     */
    public function getStatusAttribute(): string
    {
        if ($this->reserved_at) {
            return 'processing';
        }
        
        if ($this->available_at > time()) {
            return 'delayed';
        }
        
        return 'pending';
    }

    /**
     * 获取状态标签
     *
     * @return string
     */
    public function getStatusLabelAttribute(): string
    {
        switch ($this->status) {
            case 'processing':
                return '<span class="badge badge-warning">处理中</span>';
            case 'delayed':
                return '<span class="badge badge-info">延迟</span>';
            case 'pending':
                return '<span class="badge badge-primary">等待中</span>';
            default:
                return '<span class="badge badge-secondary">未知</span>';
        }
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
     * 作用域：按状态筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatus($query, string $status)
    {
        switch ($status) {
            case 'processing':
                return $query->whereNotNull('reserved_at');
            case 'delayed':
                return $query->where('available_at', '>', time());
            case 'pending':
                return $query->whereNull('reserved_at')->where('available_at', '<=', time());
            default:
                return $query;
        }
    }

    /**
     * 作用域：按尝试次数筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $attempts
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAttempts($query, int $attempts)
    {
        return $query->where('attempts', $attempts);
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
     * 获取队列统计信息
     *
     * @return array
     */
    public static function getQueueStats(): array
    {
        $total = static::count();
        $processing = static::whereNotNull('reserved_at')->count();
        $delayed = static::where('available_at', '>', time())->count();
        $pending = static::whereNull('reserved_at')->where('available_at', '<=', time())->count();
        
        return [
            'total' => $total,
            'processing' => $processing,
            'delayed' => $delayed,
            'pending' => $pending,
        ];
    }
}
