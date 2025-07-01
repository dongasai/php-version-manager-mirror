<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 镜像模型
 */
class Mirror extends Model
{
    use HasFactory;

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'url',
        'status',
        'config',
    ];

    /**
     * 属性类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'config' => 'array',
        'status' => 'integer',
    ];

    /**
     * 镜像类型常量
     */
    const TYPE_PHP = 'php';
    const TYPE_PECL = 'pecl';
    const TYPE_EXTENSION = 'extension';
    const TYPE_COMPOSER = 'composer';

    /**
     * 状态常量
     */
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    /**
     * 获取所有镜像类型
     *
     * @return array
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_PHP => 'PHP源码',
            self::TYPE_PECL => 'PECL扩展',
            self::TYPE_EXTENSION => 'GitHub扩展',
            self::TYPE_COMPOSER => 'Composer包',
        ];
    }

    /**
     * 获取状态选项
     *
     * @return array
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ENABLED => '启用',
        ];
    }

    /**
     * 获取镜像类型名称
     *
     * @return string
     */
    public function getTypeNameAttribute(): string
    {
        $types = self::getTypes();
        return $types[$this->type] ?? $this->type;
    }

    /**
     * 获取状态名称
     *
     * @return string
     */
    public function getStatusNameAttribute(): string
    {
        $statuses = self::getStatusOptions();
        return $statuses[$this->status] ?? '未知';
    }

    /**
     * 检查是否启用
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    /**
     * 启用镜像
     *
     * @return bool
     */
    public function enable(): bool
    {
        return $this->update(['status' => self::STATUS_ENABLED]);
    }

    /**
     * 禁用镜像
     *
     * @return bool
     */
    public function disable(): bool
    {
        return $this->update(['status' => self::STATUS_DISABLED]);
    }

    /**
     * 关联同步任务
     *
     * @return \Illuminate\Database\Eloquent\HasMany
     */
    public function syncJobs()
    {
        return $this->hasMany(SyncJob::class);
    }

    /**
     * 获取最新的同步任务
     *
     * @return \Illuminate\Database\Eloquent\HasOne
     */
    public function latestSyncJob()
    {
        return $this->hasOne(SyncJob::class)->latestOfMany();
    }

    /**
     * 获取正在运行的同步任务
     *
     * @return \Illuminate\Database\Eloquent\HasOne
     */
    public function runningSyncJob()
    {
        return $this->hasOne(SyncJob::class)
                   ->whereIn('status', ['pending', 'running']);
    }

    /**
     * 作用域：启用的镜像
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeEnabled($query)
    {
        return $query->where('status', self::STATUS_ENABLED);
    }

    /**
     * 作用域：按类型筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * 获取配置值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getConfig(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * 设置配置值
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setConfig(string $key, $value): void
    {
        $config = $this->config ?? [];
        data_set($config, $key, $value);
        $this->config = $config;
    }

    /**
     * 合并配置
     *
     * @param array $config
     * @return void
     */
    public function mergeConfig(array $config): void
    {
        $this->config = array_merge($this->config ?? [], $config);
    }
}
