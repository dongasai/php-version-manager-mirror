<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 系统配置模型
 */
class SystemConfig extends Model
{
    use HasFactory;

    /**
     * 数据表名
     *
     * @var string
     */
    protected $table = 'system_configs';

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * 配置分组常量
     */
    const GROUP_SYSTEM = 'system';
    const GROUP_MIRROR = 'mirror';
    const GROUP_SYNC = 'sync';
    const GROUP_CACHE = 'cache';
    const GROUP_LOG = 'log';
    const GROUP_SECURITY = 'security';

    /**
     * 获取配置分组
     *
     * @return array
     */
    public static function getGroups(): array
    {
        return [
            self::GROUP_SYSTEM => '系统配置',
            self::GROUP_MIRROR => '镜像配置',
            self::GROUP_SYNC => '同步配置',
            self::GROUP_CACHE => '缓存配置',
            self::GROUP_LOG => '日志配置',
            self::GROUP_SECURITY => '安全配置',
        ];
    }

    /**
     * 获取配置分组名称
     *
     * @return string
     */
    public function getGroupNameAttribute(): string
    {
        $group = $this->getGroupFromKey();
        $groups = self::getGroups();
        return $groups[$group] ?? '其他';
    }

    /**
     * 从键名获取分组
     *
     * @return string
     */
    public function getGroupFromKey(): string
    {
        $parts = explode('.', $this->key);
        return $parts[0] ?? 'other';
    }

    /**
     * 获取解析后的值
     *
     * @return mixed
     */
    public function getParsedValueAttribute()
    {
        $decoded = json_decode($this->value, true);
        return $decoded !== null ? $decoded : $this->value;
    }

    /**
     * 检查是否为JSON值
     *
     * @return bool
     */
    public function isJsonValue(): bool
    {
        json_decode($this->value);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * 获取值类型
     *
     * @return string
     */
    public function getValueTypeAttribute(): string
    {
        if ($this->isJsonValue()) {
            $decoded = json_decode($this->value, true);
            if (is_array($decoded)) {
                return 'array';
            } elseif (is_bool($decoded)) {
                return 'boolean';
            } elseif (is_numeric($decoded)) {
                return 'number';
            }
            return 'json';
        }

        if (is_numeric($this->value)) {
            return 'number';
        }

        if (in_array(strtolower($this->value), ['true', 'false'])) {
            return 'boolean';
        }

        return 'string';
    }

    /**
     * 设置值（自动处理JSON编码）
     *
     * @param mixed $value
     * @return void
     */
    public function setValue($value): void
    {
        if (is_array($value) || is_object($value)) {
            $this->value = json_encode($value, JSON_UNESCAPED_UNICODE);
        } elseif (is_bool($value)) {
            $this->value = json_encode($value);
        } else {
            $this->value = (string) $value;
        }
    }

    /**
     * 作用域：按分组筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $group
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInGroup($query, string $group)
    {
        return $query->where('key', 'like', $group . '.%');
    }

    /**
     * 作用域：按键名搜索
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('key', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('value', 'like', "%{$search}%");
        });
    }

    /**
     * 获取默认配置
     *
     * @return array
     */
    public static function getDefaultConfigs(): array
    {
        return [
            'system.data_dir' => [
                'value' => base_path('data'),
                'description' => '镜像数据存储目录',
            ],
            'system.cache_dir' => [
                'value' => storage_path('app/mirror-cache'),
                'description' => '镜像缓存目录',
            ],
            'system.temp_dir' => [
                'value' => storage_path('app/temp'),
                'description' => '临时文件目录',
            ],
            'server.host' => [
                'value' => '0.0.0.0',
                'description' => '服务器监听地址',
            ],
            'server.port' => [
                'value' => 8080,
                'description' => '服务器监听端口',
            ],
            'server.public_url' => [
                'value' => 'http://localhost:8080',
                'description' => '公开访问URL',
            ],
            'sync.interval' => [
                'value' => 24,
                'description' => '自动同步间隔（小时）',
            ],
            'sync.max_retries' => [
                'value' => 3,
                'description' => '最大重试次数',
            ],
            'sync.retry_interval' => [
                'value' => 300,
                'description' => '重试间隔（秒）',
            ],
            'sync.concurrent_downloads' => [
                'value' => 5,
                'description' => '并发下载数',
            ],
            'cache.enable' => [
                'value' => true,
                'description' => '启用缓存',
            ],
            'cache.ttl' => [
                'value' => 3600,
                'description' => '缓存生存时间（秒）',
            ],
            'log.enable_logging' => [
                'value' => true,
                'description' => '启用日志记录',
            ],
            'log.log_level' => [
                'value' => 'info',
                'description' => '日志级别',
            ],
            'log.max_log_size' => [
                'value' => 10485760, // 10MB
                'description' => '最大日志文件大小（字节）',
            ],
            'log.max_log_files' => [
                'value' => 10,
                'description' => '最大日志文件数量',
            ],
            'security.enable_access_control' => [
                'value' => false,
                'description' => '启用访问控制',
            ],
            'security.allowed_ips' => [
                'value' => [],
                'description' => '允许访问的IP地址列表',
            ],
            'security.rate_limit.enabled' => [
                'value' => true,
                'description' => '启用访问频率限制',
            ],
            'security.rate_limit.max_requests' => [
                'value' => 100,
                'description' => '最大请求数',
            ],
            'security.rate_limit.window_minutes' => [
                'value' => 60,
                'description' => '时间窗口（分钟）',
            ],
        ];
    }

    /**
     * 初始化默认配置
     *
     * @return void
     */
    public static function initializeDefaults(): void
    {
        $defaults = self::getDefaultConfigs();

        foreach ($defaults as $key => $config) {
            self::firstOrCreate(
                ['key' => $key],
                [
                    'value' => is_array($config['value']) || is_object($config['value']) 
                        ? json_encode($config['value'], JSON_UNESCAPED_UNICODE)
                        : (string) $config['value'],
                    'description' => $config['description'],
                ]
            );
        }
    }
}
