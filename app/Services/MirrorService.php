<?php

namespace App\Services;

use App\Models\Mirror;
use App\Models\SyncJob;
use App\Jobs\SyncMirrorJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

/**
 * 镜像服务
 *
 * 负责管理镜像源，处理文件下载和同步
 * 使用硬编码配置而不是数据库配置
 */
class MirrorService
{
    /**
     * 配置服务
     *
     * @var ConfigService
     */
    protected $configService;

    /**
     * 缓存服务
     *
     * @var CacheService
     */
    protected $cacheService;

    /**
     * 镜像配置服务
     *
     * @var MirrorConfigService
     */
    protected $mirrorConfigService;

    /**
     * 构造函数
     *
     * @param ConfigService $configService
     * @param CacheService $cacheService
     * @param MirrorConfigService $mirrorConfigService
     */
    public function __construct(ConfigService $configService, CacheService $cacheService, MirrorConfigService $mirrorConfigService)
    {
        $this->configService = $configService;
        $this->cacheService = $cacheService;
        $this->mirrorConfigService = $mirrorConfigService;
    }

    /**
     * 获取所有启用的镜像配置
     *
     * @return array
     */
    public function getAllMirrors()
    {
        $mirrors = [];
        $enabledTypes = $this->mirrorConfigService->getEnabledMirrorTypes();

        foreach ($enabledTypes as $type) {
            $mirrors[] = $this->getMirrorConfigByType($type);
        }

        return $mirrors;
    }

    /**
     * 根据类型获取镜像配置
     *
     * @param string $type 镜像类型
     * @return array
     */
    public function getMirrorsByType(string $type)
    {
        $config = $this->getMirrorConfigByType($type);
        return $config ? [$config] : [];
    }

    /**
     * 根据类型获取单个镜像配置
     *
     * @param string $type 镜像类型
     * @return array|null
     */
    protected function getMirrorConfigByType(string $type): ?array
    {
        switch ($type) {
            case 'php':
                $config = $this->mirrorConfigService->getPhpConfig();
                return $config['enabled'] ? [
                    'type' => 'php',
                    'name' => 'PHP源码镜像',
                    'config' => $config,
                ] : null;

            case 'pecl':
                $config = $this->mirrorConfigService->getPeclConfig();
                return $config['enabled'] ? [
                    'type' => 'pecl',
                    'name' => 'PECL扩展镜像',
                    'config' => $config,
                ] : null;

            case 'github':
                $config = $this->mirrorConfigService->getGithubConfig();
                return $config['enabled'] ? [
                    'type' => 'github',
                    'name' => 'GitHub扩展镜像',
                    'config' => $config,
                ] : null;

            case 'composer':
                $config = $this->mirrorConfigService->getComposerConfig();
                return $config['enabled'] ? [
                    'type' => 'composer',
                    'name' => 'Composer镜像',
                    'config' => $config,
                ] : null;

            default:
                return null;
        }
    }

    /**
     * 创建镜像
     *
     * @param array $data 镜像数据
     * @return Mirror
     */
    public function createMirror(array $data): Mirror
    {
        $mirror = Mirror::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'url' => $data['url'],
            'status' => $data['status'] ?? 1,
            'config' => $data['config'] ?? [],
        ]);

        Log::info("镜像创建成功", ['mirror_id' => $mirror->id, 'name' => $mirror->name]);

        return $mirror;
    }

    /**
     * 更新镜像
     *
     * @param int $id 镜像ID
     * @param array $data 更新数据
     * @return bool
     */
    public function updateMirror(int $id, array $data): bool
    {
        try {
            $mirror = Mirror::findOrFail($id);
            $mirror->update($data);

            Log::info("镜像更新成功", ['mirror_id' => $id]);

            return true;
        } catch (\Exception $e) {
            Log::error("镜像更新失败", [
                'mirror_id' => $id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * 删除镜像
     *
     * @param int $id 镜像ID
     * @return bool
     */
    public function deleteMirror(int $id): bool
    {
        try {
            $mirror = Mirror::findOrFail($id);
            $mirror->delete();

            Log::info("镜像删除成功", ['mirror_id' => $id]);

            return true;
        } catch (\Exception $e) {
            Log::error("镜像删除失败", [
                'mirror_id' => $id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * 同步指定类型的镜像
     *
     * @param string $type 镜像类型
     * @param bool $force 是否强制同步
     * @return SyncJob
     */
    public function syncMirrorByType(string $type, bool $force = false): SyncJob
    {
        // 检查是否已有正在进行的同步任务
        if (!$force) {
            $existingJob = SyncJob::where('mirror_type', $type)
                                 ->whereIn('status', ['pending', 'running'])
                                 ->first();

            if ($existingJob) {
                Log::warning("镜像同步任务已存在", [
                    'mirror_type' => $type,
                    'job_id' => $existingJob->id
                ]);
                return $existingJob;
            }
        }

        // 创建同步任务
        $syncJob = SyncJob::create([
            'mirror_type' => $type,
            'status' => 'pending',
            'progress' => 0,
            'log' => '',
        ]);

        // 分发同步任务到队列
        SyncMirrorJob::dispatch($syncJob);

        Log::info("镜像同步任务已创建", [
            'mirror_type' => $type,
            'job_id' => $syncJob->id
        ]);

        return $syncJob;
    }

    /**
     * 同步所有启用的镜像
     *
     * @param bool $force 是否强制同步
     * @return array
     */
    public function syncAllMirrors(bool $force = false): array
    {
        $enabledTypes = $this->mirrorConfigService->getEnabledMirrorTypes();
        $jobs = [];

        foreach ($enabledTypes as $type) {
            try {
                $job = $this->syncMirrorByType($type, $force);
                $jobs[] = $job;
            } catch (\Exception $e) {
                Log::error("镜像同步失败", [
                    'mirror_type' => $type,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $jobs;
    }

    /**
     * 获取镜像状态
     *
     * @param int $mirrorId 镜像ID
     * @return array
     */
    public function getMirrorStatus(int $mirrorId): array
    {
        $mirror = Mirror::findOrFail($mirrorId);
        
        // 获取最新的同步任务
        $latestJob = SyncJob::where('mirror_id', $mirrorId)
                           ->orderBy('created_at', 'desc')
                           ->first();

        // 获取文件统计
        $stats = $this->getMirrorStats($mirror);

        return [
            'mirror' => $mirror,
            'latest_job' => $latestJob,
            'stats' => $stats,
        ];
    }

    /**
     * 获取镜像统计信息
     *
     * @param Mirror $mirror 镜像对象
     * @return array
     */
    public function getMirrorStats(Mirror $mirror): array
    {
        $dataDir = $this->configService->getDataDir();
        $mirrorDir = $dataDir . '/' . $mirror->type;

        if (!is_dir($mirrorDir)) {
            return [
                'file_count' => 0,
                'total_size' => 0,
                'last_updated' => null,
            ];
        }

        $fileCount = 0;
        $totalSize = 0;
        $lastUpdated = null;

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($mirrorDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $fileCount++;
                $totalSize += $file->getSize();
                
                $mtime = $file->getMTime();
                if ($lastUpdated === null || $mtime > $lastUpdated) {
                    $lastUpdated = $mtime;
                }
            }
        }

        return [
            'file_count' => $fileCount,
            'total_size' => $totalSize,
            'last_updated' => $lastUpdated ? date('Y-m-d H:i:s', $lastUpdated) : null,
        ];
    }

    /**
     * 处理文件下载请求
     *
     * @param string $path 文件路径
     * @return array|null
     */
    public function handleFileRequest(string $path): ?array
    {
        $dataDir = $this->configService->getDataDir();
        $fullPath = $dataDir . '/' . ltrim($path, '/');

        // 安全检查：确保路径在数据目录内
        $realDataDir = realpath($dataDir);
        $realFilePath = realpath($fullPath);

        if (!$realFilePath || strpos($realFilePath, $realDataDir) !== 0) {
            Log::warning("非法文件访问尝试", ['path' => $path]);
            return null;
        }

        if (!file_exists($fullPath) || !is_file($fullPath)) {
            return null;
        }

        return [
            'path' => $fullPath,
            'size' => filesize($fullPath),
            'mime_type' => mime_content_type($fullPath) ?: 'application/octet-stream',
            'last_modified' => filemtime($fullPath),
        ];
    }

    /**
     * 获取目录列表
     *
     * @param string $path 目录路径
     * @return array|null
     */
    public function getDirectoryListing(string $path): ?array
    {
        $dataDir = $this->configService->getDataDir();
        $fullPath = $dataDir . '/' . ltrim($path, '/');

        // 安全检查
        $realDataDir = realpath($dataDir);
        $realDirPath = realpath($fullPath);

        if (!$realDirPath || strpos($realDirPath, $realDataDir) !== 0) {
            return null;
        }

        if (!is_dir($fullPath)) {
            return null;
        }

        $items = [];
        $iterator = new \DirectoryIterator($fullPath);

        foreach ($iterator as $item) {
            if ($item->isDot()) {
                continue;
            }

            $items[] = [
                'name' => $item->getFilename(),
                'type' => $item->isDir() ? 'directory' : 'file',
                'size' => $item->isFile() ? $item->getSize() : null,
                'modified' => $item->getMTime(),
            ];
        }

        // 排序：目录在前，文件在后，按名称排序
        usort($items, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'directory' ? -1 : 1;
            }
            return strcmp($a['name'], $b['name']);
        });

        return $items;
    }
}
