<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mirror;
use App\Models\SyncJob;
use App\Services\MirrorService;
use App\Services\CacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    /**
     * 镜像服务
     *
     * @var MirrorService
     */
    protected $mirrorService;

    /**
     * 缓存服务
     *
     * @var CacheService
     */
    protected $cacheService;

    /**
     * 构造函数
     *
     * @param MirrorService $mirrorService
     * @param CacheService $cacheService
     */
    public function __construct(MirrorService $mirrorService, CacheService $cacheService)
    {
        $this->mirrorService = $mirrorService;
        $this->cacheService = $cacheService;
    }

    /**
     * 获取系统状态
     *
     * @return JsonResponse
     */
    public function status(): JsonResponse
    {
        $data = $this->cacheService->remember('api_status', function () {
            $mirrors = Mirror::enabled()->with('latestSyncJob')->get();
            $stats = [];

            foreach ($mirrors as $mirror) {
                $mirrorStats = $this->mirrorService->getMirrorStats($mirror);
                $stats[$mirror->type] = [
                    'name' => $mirror->name,
                    'enabled' => $mirror->isEnabled(),
                    'file_count' => $mirrorStats['file_count'],
                    'total_size' => $mirrorStats['total_size'],
                    'last_updated' => $mirrorStats['last_updated'],
                    'latest_job' => $mirror->latestSyncJob ? [
                        'id' => $mirror->latestSyncJob->id,
                        'status' => $mirror->latestSyncJob->status,
                        'progress' => $mirror->latestSyncJob->progress,
                        'started_at' => $mirror->latestSyncJob->started_at,
                        'completed_at' => $mirror->latestSyncJob->completed_at,
                    ] : null,
                ];
            }

            return [
                'server' => 'pvm-mirror-laravel',
                'version' => '2.0.0',
                'timestamp' => time(),
                'mirrors' => $stats,
                'jobs' => [
                    'total' => SyncJob::count(),
                    'running' => SyncJob::running()->count(),
                    'completed' => SyncJob::completed()->count(),
                    'failed' => SyncJob::failed()->count(),
                ],
            ];
        }, 300); // 缓存5分钟

        return response()->json($data);
    }

    /**
     * 获取PHP版本列表
     *
     * @return JsonResponse
     */
    public function php(): JsonResponse
    {
        return $this->cacheService->remember('api_php', function () {
            $mirror = Mirror::where('type', 'php')->enabled()->first();

            if (!$mirror) {
                return ['error' => 'PHP mirror not found or disabled'];
            }

            $config = $mirror->config;
            $versions = [];

            if (isset($config['versions'])) {
                foreach ($config['versions'] as $majorVersion => $versionList) {
                    $versions[$majorVersion] = $versionList;
                }
            }

            return [
                'type' => 'php',
                'name' => $mirror->name,
                'versions' => $versions,
                'source' => $config['source'] ?? null,
            ];
        }, 3600); // 缓存1小时
    }

    /**
     * 获取PECL扩展列表
     *
     * @return JsonResponse
     */
    public function pecl(): JsonResponse
    {
        return $this->cacheService->remember('api_pecl', function () {
            $mirror = Mirror::where('type', 'pecl')->enabled()->first();

            if (!$mirror) {
                return ['error' => 'PECL mirror not found or disabled'];
            }

            $config = $mirror->config;
            $extensions = $config['extensions'] ?? [];

            return [
                'type' => 'pecl',
                'name' => $mirror->name,
                'extensions' => $extensions,
            ];
        }, 3600); // 缓存1小时
    }

    /**
     * 获取GitHub扩展列表
     *
     * @return JsonResponse
     */
    public function extensions(): JsonResponse
    {
        return $this->cacheService->remember('api_extensions', function () {
            $mirror = Mirror::where('type', 'extension')->enabled()->first();

            if (!$mirror) {
                return ['error' => 'Extension mirror not found or disabled'];
            }

            $config = $mirror->config;
            $extensions = $config['github_extensions'] ?? [];

            return [
                'type' => 'extension',
                'name' => $mirror->name,
                'extensions' => $extensions,
            ];
        }, 3600); // 缓存1小时
    }

    /**
     * 获取Composer包列表
     *
     * @return JsonResponse
     */
    public function composer(): JsonResponse
    {
        return $this->cacheService->remember('api_composer', function () {
            $mirror = Mirror::where('type', 'composer')->enabled()->first();

            if (!$mirror) {
                return ['error' => 'Composer mirror not found or disabled'];
            }

            $config = $mirror->config;

            return [
                'type' => 'composer',
                'name' => $mirror->name,
                'config' => $config,
            ];
        }, 3600); // 缓存1小时
    }
}
