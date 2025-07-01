<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 访问日志模型
 */
class AccessLog extends Model
{
    use HasFactory;

    /**
     * 数据表名
     *
     * @var string
     */
    protected $table = 'access_logs';

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ip',
        'user_agent',
        'method',
        'path',
        'status',
        'size',
    ];

    /**
     * 属性类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'integer',
        'size' => 'integer',
    ];

    /**
     * 状态码分类
     */
    const STATUS_SUCCESS = 'success';
    const STATUS_REDIRECT = 'redirect';
    const STATUS_CLIENT_ERROR = 'client_error';
    const STATUS_SERVER_ERROR = 'server_error';

    /**
     * 获取状态码分类
     *
     * @return string
     */
    public function getStatusCategoryAttribute(): string
    {
        return match (true) {
            $this->status >= 200 && $this->status < 300 => self::STATUS_SUCCESS,
            $this->status >= 300 && $this->status < 400 => self::STATUS_REDIRECT,
            $this->status >= 400 && $this->status < 500 => self::STATUS_CLIENT_ERROR,
            $this->status >= 500 => self::STATUS_SERVER_ERROR,
            default => 'unknown',
        };
    }

    /**
     * 获取状态码颜色
     *
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status_category) {
            self::STATUS_SUCCESS => 'success',
            self::STATUS_REDIRECT => 'info',
            self::STATUS_CLIENT_ERROR => 'warning',
            self::STATUS_SERVER_ERROR => 'danger',
            default => 'secondary',
        };
    }

    /**
     * 获取格式化的响应大小
     *
     * @return string
     */
    public function getFormattedResponseSizeAttribute(): string
    {
        return $this->formatBytes($this->size ?? 0);
    }

    /**
     * 获取格式化的响应时间
     *
     * @return string
     */
    public function getFormattedResponseTimeAttribute(): string
    {
        $time = $this->response_time ?? 0;
        
        if ($time < 1) {
            return round($time * 1000) . 'ms';
        } else {
            return round($time, 2) . 's';
        }
    }

    /**
     * 获取浏览器信息
     *
     * @return string
     */
    public function getBrowserAttribute(): string
    {
        $userAgent = $this->user_agent ?? '';
        
        if (str_contains($userAgent, 'Chrome')) {
            return 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            return 'Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            return 'Edge';
        } elseif (str_contains($userAgent, 'curl')) {
            return 'cURL';
        } elseif (str_contains($userAgent, 'wget')) {
            return 'Wget';
        } else {
            return 'Unknown';
        }
    }

    /**
     * 检查是否为机器人访问
     *
     * @return bool
     */
    public function isBotAttribute(): bool
    {
        $userAgent = strtolower($this->user_agent ?? '');
        $botKeywords = ['bot', 'crawler', 'spider', 'scraper', 'curl', 'wget'];
        
        foreach ($botKeywords as $keyword) {
            if (str_contains($userAgent, $keyword)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 作用域：按IP地址筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $ip
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * 作用域：按状态码筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $statusCode
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, int $statusCode)
    {
        return $query->where('status', $statusCode);
    }

    /**
     * 作用域：按状态码分类筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatusCategory($query, string $category)
    {
        return match ($category) {
            self::STATUS_SUCCESS => $query->whereBetween('status', [200, 299]),
            self::STATUS_REDIRECT => $query->whereBetween('status', [300, 399]),
            self::STATUS_CLIENT_ERROR => $query->whereBetween('status', [400, 499]),
            self::STATUS_SERVER_ERROR => $query->where('status', '>=', 500),
            default => $query,
        };
    }

    /**
     * 作用域：按方法筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $method
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', strtoupper($method));
    }

    /**
     * 作用域：按URL路径筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $path
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPath($query, string $path)
    {
        return $query->where('url', 'like', "%{$path}%");
    }

    /**
     * 作用域：今天的访问
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * 作用域：最近N天的访问
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentDays($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * 作用域：慢请求
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $threshold
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSlowRequests($query, float $threshold = 1.0)
    {
        return $query->where('response_time', '>', $threshold);
    }

    /**
     * 作用域：大文件下载
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $threshold
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLargeDownloads($query, int $threshold = 10485760) // 10MB
    {
        return $query->where('response_size', '>', $threshold);
    }

    /**
     * 格式化字节大小
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * 记录访问日志
     *
     * @param array $data
     * @return static
     */
    public static function logAccess(array $data): self
    {
        return self::create([
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'method' => $data['method'] ?? request()->method(),
            'url' => $data['url'] ?? request()->fullUrl(),
            'status_code' => $data['status_code'] ?? 200,
            'response_size' => $data['response_size'] ?? 0,
            'response_time' => $data['response_time'] ?? 0,
            'referer' => $data['referer'] ?? request()->header('referer'),
            'accessed_at' => $data['accessed_at'] ?? now(),
        ]);
    }

    /**
     * 清理旧日志
     *
     * @param int $days
     * @return int
     */
    public static function cleanupOldLogs(int $days = 90): int
    {
        return self::where('accessed_at', '<', now()->subDays($days))->delete();
    }

    /**
     * 获取访问统计
     *
     * @param int $days
     * @return array
     */
    public static function getStats(int $days = 7): array
    {
        $query = self::recentDays($days);

        return [
            'total_requests' => $query->count(),
            'unique_ips' => $query->distinct('ip')->count(),
            'success_rate' => $query->whereBetween('status', [200, 299])->count() / max($query->count(), 1) * 100,
            'avg_response_time' => 0, // 暂时返回0，因为表中没有response_time字段
            'total_bandwidth' => $query->sum('size') ?? 0,
            'top_ips' => $query->selectRaw('ip, COUNT(*) as count')
                              ->groupBy('ip')
                              ->orderByDesc('count')
                              ->limit(10)
                              ->get(),
            'top_paths' => $query->selectRaw('path, COUNT(*) as count')
                                ->groupBy('path')
                                ->orderByDesc('count')
                                ->limit(10)
                                ->get(),
        ];
    }
}
