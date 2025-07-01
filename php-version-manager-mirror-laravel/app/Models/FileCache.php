<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 文件缓存模型
 */
class FileCache extends Model
{
    use HasFactory;

    /**
     * 数据表名
     *
     * @var string
     */
    protected $table = 'file_cache';

    /**
     * 可批量赋值的属性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'file_path',
        'file_hash',
        'file_size',
        'mime_type',
        'last_modified',
        'metadata',
    ];

    /**
     * 属性类型转换
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'last_modified' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * 获取格式化的文件大小
     *
     * @return string
     */
    public function getFormattedSizeAttribute(): string
    {
        return $this->formatBytes($this->file_size);
    }

    /**
     * 获取文件扩展名
     *
     * @return string
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->file_path, PATHINFO_EXTENSION);
    }

    /**
     * 获取文件名
     *
     * @return string
     */
    public function getFilenameAttribute(): string
    {
        return basename($this->file_path);
    }

    /**
     * 获取目录路径
     *
     * @return string
     */
    public function getDirectoryAttribute(): string
    {
        return dirname($this->file_path);
    }

    /**
     * 检查文件是否存在
     *
     * @return bool
     */
    public function fileExists(): bool
    {
        return file_exists($this->file_path);
    }

    /**
     * 检查文件是否已修改
     *
     * @return bool
     */
    public function isModified(): bool
    {
        if (!$this->fileExists()) {
            return true;
        }

        $currentMtime = filemtime($this->file_path);
        return $currentMtime > $this->last_modified->timestamp;
    }

    /**
     * 更新文件信息
     *
     * @return bool
     */
    public function updateFileInfo(): bool
    {
        if (!$this->fileExists()) {
            return false;
        }

        $this->file_size = filesize($this->file_path);
        $this->last_modified = date('Y-m-d H:i:s', filemtime($this->file_path));
        $this->mime_type = mime_content_type($this->file_path) ?: 'application/octet-stream';
        $this->file_hash = md5_file($this->file_path);

        return $this->save();
    }

    /**
     * 获取元数据值
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMetadata(string $key, $default = null)
    {
        return data_get($this->metadata, $key, $default);
    }

    /**
     * 设置元数据值
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function setMetadata(string $key, $value): void
    {
        $metadata = $this->metadata ?? [];
        data_set($metadata, $key, $value);
        $this->metadata = $metadata;
    }

    /**
     * 合并元数据
     *
     * @param array $metadata
     * @return void
     */
    public function mergeMetadata(array $metadata): void
    {
        $this->metadata = array_merge($this->metadata ?? [], $metadata);
    }

    /**
     * 作用域：按文件路径搜索
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $path
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPath($query, string $path)
    {
        return $query->where('file_path', 'like', "%{$path}%");
    }

    /**
     * 作用域：按MIME类型筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $mimeType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByMimeType($query, string $mimeType)
    {
        return $query->where('mime_type', $mimeType);
    }

    /**
     * 作用域：按文件大小范围筛选
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $minSize
     * @param int|null $maxSize
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySizeRange($query, int $minSize, ?int $maxSize = null)
    {
        $query->where('file_size', '>=', $minSize);
        
        if ($maxSize !== null) {
            $query->where('file_size', '<=', $maxSize);
        }
        
        return $query;
    }

    /**
     * 作用域：最近修改的文件
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecentlyModified($query, int $days = 7)
    {
        return $query->where('last_modified', '>=', now()->subDays($days));
    }

    /**
     * 作用域：过期的缓存记录
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpired($query, int $days = 30)
    {
        return $query->where('updated_at', '<', now()->subDays($days));
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
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * 创建或更新文件缓存
     *
     * @param string $filePath
     * @return static|null
     */
    public static function createOrUpdateFromFile(string $filePath): ?self
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $fileSize = filesize($filePath);
        $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
        $lastModified = date('Y-m-d H:i:s', filemtime($filePath));
        $fileHash = md5_file($filePath);

        return self::updateOrCreate(
            ['file_path' => $filePath],
            [
                'file_hash' => $fileHash,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'last_modified' => $lastModified,
                'metadata' => [
                    'created_at' => now()->toISOString(),
                    'extension' => pathinfo($filePath, PATHINFO_EXTENSION),
                    'filename' => basename($filePath),
                    'directory' => dirname($filePath),
                ],
            ]
        );
    }

    /**
     * 批量清理不存在的文件缓存
     *
     * @return int
     */
    public static function cleanupMissingFiles(): int
    {
        $count = 0;
        
        self::chunk(100, function ($fileCaches) use (&$count) {
            foreach ($fileCaches as $fileCache) {
                if (!$fileCache->fileExists()) {
                    $fileCache->delete();
                    $count++;
                }
            }
        });

        return $count;
    }
}
