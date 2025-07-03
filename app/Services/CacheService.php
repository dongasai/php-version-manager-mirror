<?php

namespace App\Services;

use App\Models\FileCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * 缓存服务
 * 
 * 负责管理文件缓存、内存缓存等缓存相关功能
 */
class CacheService
{
    /**
     * 配置服务
     *
     * @var ConfigService
     */
    protected $configService;

    /**
     * 缓存键前缀
     */
    const CACHE_PREFIX = 'mirror:';

    /**
     * 默认缓存时间（秒）
     */
    const DEFAULT_TTL = 3600;

    /**
     * 构造函数
     *
     * @param ConfigService $configService
     */
    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * 获取缓存值
     *
     * @param string $key 缓存键
     * @param mixed $default 默认值
     * @param int|null $ttl 缓存时间
     * @return mixed
     */
    public function get(string $key, $default = null, ?int $ttl = null)
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        return Cache::get($cacheKey, $default);
    }

    /**
     * 设置缓存值
     *
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int|null $ttl 缓存时间
     * @return bool
     */
    public function set(string $key, $value, ?int $ttl = null): bool
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        $ttl = $ttl ?? self::DEFAULT_TTL;
        
        return Cache::put($cacheKey, $value, $ttl);
    }

    /**
     * 删除缓存
     *
     * @param string $key 缓存键
     * @return bool
     */
    public function delete(string $key): bool
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        return Cache::forget($cacheKey);
    }

    /**
     * 检查缓存是否存在
     *
     * @param string $key 缓存键
     * @return bool
     */
    public function has(string $key): bool
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        return Cache::has($cacheKey);
    }

    /**
     * 记住缓存（如果不存在则执行回调并缓存结果）
     *
     * @param string $key 缓存键
     * @param callable $callback 回调函数
     * @param int|null $ttl 缓存时间
     * @return mixed
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        $ttl = $ttl ?? self::DEFAULT_TTL;
        
        return Cache::remember($cacheKey, $ttl, $callback);
    }

    /**
     * 缓存文件信息
     *
     * @param string $filePath 文件路径
     * @param array $metadata 文件元数据
     * @return bool
     */
    public function cacheFileInfo(string $filePath, array $metadata): bool
    {
        try {
            $hash = md5($filePath);
            
            FileCache::updateOrCreate(
                ['file_path' => $filePath],
                [
                    'file_hash' => $hash,
                    'file_size' => $metadata['size'] ?? 0,
                    'mime_type' => $metadata['mime_type'] ?? 'application/octet-stream',
                    'last_modified' => $metadata['last_modified'] ?? now(),
                    'metadata' => $metadata,
                ]
            );

            // 同时缓存到内存
            $this->set("file_info:{$hash}", $metadata, 7200); // 2小时

            return true;
        } catch (\Exception $e) {
            Log::error("文件信息缓存失败", [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * 获取文件缓存信息
     *
     * @param string $filePath 文件路径
     * @return array|null
     */
    public function getFileInfo(string $filePath): ?array
    {
        $hash = md5($filePath);
        
        // 先从内存缓存获取
        $cached = $this->get("file_info:{$hash}");
        if ($cached) {
            return $cached;
        }

        // 从数据库获取
        $fileCache = FileCache::where('file_path', $filePath)->first();
        if ($fileCache) {
            $metadata = $fileCache->metadata;
            
            // 重新缓存到内存
            $this->set("file_info:{$hash}", $metadata, 7200);
            
            return $metadata;
        }

        return null;
    }

    /**
     * 删除文件缓存
     *
     * @param string $filePath 文件路径
     * @return bool
     */
    public function deleteFileCache(string $filePath): bool
    {
        try {
            $hash = md5($filePath);
            
            // 删除数据库记录
            FileCache::where('file_path', $filePath)->delete();
            
            // 删除内存缓存
            $this->delete("file_info:{$hash}");

            return true;
        } catch (\Exception $e) {
            Log::error("文件缓存删除失败", [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * 清理过期的文件缓存
     *
     * @param int $days 保留天数
     * @return int 清理的记录数
     */
    public function cleanExpiredFileCache(int $days = 30): int
    {
        try {
            $expiredDate = now()->subDays($days);
            
            $count = FileCache::where('updated_at', '<', $expiredDate)->count();
            FileCache::where('updated_at', '<', $expiredDate)->delete();

            Log::info("文件缓存清理完成", [
                'cleaned_count' => $count,
                'days' => $days
            ]);

            return $count;
        } catch (\Exception $e) {
            Log::error("文件缓存清理失败", ['error' => $e->getMessage()]);
            return 0;
        }
    }

    /**
     * 获取缓存统计信息
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        try {
            $fileCacheCount = FileCache::count();
            $fileCacheSize = FileCache::sum('file_size');
            
            return [
                'file_cache_count' => $fileCacheCount,
                'file_cache_size' => $fileCacheSize,
                'cache_hit_rate' => $this->getCacheHitRate(),
            ];
        } catch (\Exception $e) {
            Log::error("获取缓存统计失败", ['error' => $e->getMessage()]);
            
            return [
                'file_cache_count' => 0,
                'file_cache_size' => 0,
                'cache_hit_rate' => 0,
            ];
        }
    }

    /**
     * 获取缓存命中率
     *
     * @return float
     */
    protected function getCacheHitRate(): float
    {
        $hits = $this->get('cache_hits', 0);
        $misses = $this->get('cache_misses', 0);
        $total = $hits + $misses;
        
        return $total > 0 ? round($hits / $total * 100, 2) : 0;
    }

    /**
     * 记录缓存命中
     *
     * @return void
     */
    public function recordCacheHit(): void
    {
        $hits = $this->get('cache_hits', 0);
        $this->set('cache_hits', $hits + 1, 86400); // 24小时
    }

    /**
     * 记录缓存未命中
     *
     * @return void
     */
    public function recordCacheMiss(): void
    {
        $misses = $this->get('cache_misses', 0);
        $this->set('cache_misses', $misses + 1, 86400); // 24小时
    }

    /**
     * 清除所有缓存
     *
     * @return bool
     */
    public function clearAll(): bool
    {
        try {
            // 清除内存缓存
            Cache::flush();
            
            // 清除文件缓存记录
            FileCache::truncate();

            Log::info("所有缓存已清除");

            return true;
        } catch (\Exception $e) {
            Log::error("清除缓存失败", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 预热缓存
     *
     * @param array $keys 需要预热的缓存键
     * @return void
     */
    public function warmup(array $keys = []): void
    {
        Log::info("开始缓存预热", ['keys_count' => count($keys)]);

        foreach ($keys as $key => $callback) {
            try {
                if (is_callable($callback)) {
                    $this->remember($key, $callback);
                }
            } catch (\Exception $e) {
                Log::warning("缓存预热失败", [
                    'key' => $key,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info("缓存预热完成");
    }

    /**
     * 获取缓存目录大小
     *
     * @return int
     */
    public function getCacheDirectorySize(): int
    {
        $cacheDir = $this->configService->getCacheDir();
        
        if (!is_dir($cacheDir)) {
            return 0;
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cacheDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * 清理缓存目录
     *
     * @param int $maxSizeMB 最大大小（MB）
     * @return bool
     */
    public function cleanCacheDirectory(int $maxSizeMB = 1024): bool
    {
        try {
            $cacheDir = $this->configService->getCacheDir();
            $maxSize = $maxSizeMB * 1024 * 1024; // 转换为字节
            
            $currentSize = $this->getCacheDirectorySize();
            
            if ($currentSize <= $maxSize) {
                return true;
            }

            // 获取所有文件并按修改时间排序
            $files = [];
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($cacheDir, \RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files[] = [
                        'path' => $file->getPathname(),
                        'size' => $file->getSize(),
                        'mtime' => $file->getMTime(),
                    ];
                }
            }

            // 按修改时间排序（最旧的在前）
            usort($files, function ($a, $b) {
                return $a['mtime'] - $b['mtime'];
            });

            // 删除最旧的文件直到大小满足要求
            $deletedSize = 0;
            foreach ($files as $file) {
                if ($currentSize - $deletedSize <= $maxSize) {
                    break;
                }

                if (unlink($file['path'])) {
                    $deletedSize += $file['size'];
                }
            }

            Log::info("缓存目录清理完成", [
                'deleted_size' => $deletedSize,
                'current_size' => $currentSize - $deletedSize
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("缓存目录清理失败", ['error' => $e->getMessage()]);
            return false;
        }
    }
}
