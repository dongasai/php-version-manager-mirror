<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ConfigService;
use App\Services\MirrorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatusController extends Controller
{
    protected ConfigService $configService;
    protected MirrorService $mirrorService;

    public function __construct(ConfigService $configService, MirrorService $mirrorService)
    {
        $this->configService = $configService;
        $this->mirrorService = $mirrorService;
    }

    /**
     * 获取系统状态
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // 使用缓存，5分钟过期
            $status = Cache::remember('api.status', 300, function () {
                return $this->getSystemStatus();
            });

            return response()->json([
                'success' => true,
                'data' => $status,
                'timestamp' => now()->toISOString(),
                'cache_ttl' => 300
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SYSTEM_ERROR',
                    'message' => 'Failed to get system status',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }

    /**
     * 获取系统状态数据
     *
     * @return array
     */
    protected function getSystemStatus(): array
    {
        $startTime = microtime(true);

        // 基本系统信息
        $status = [
            'status' => 'running',
            'version' => config('app.version', '2.0.0'),
            'uptime' => $this->getUptime(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'cache' => $this->getCacheStatus(),
            'mirrors' => $this->getMirrorStatus(),
            'database' => $this->getDatabaseStatus(),
            'performance' => [
                'response_time' => round((microtime(true) - $startTime) * 1000, 2),
                'load_average' => $this->getLoadAverage()
            ]
        ];

        return $status;
    }

    /**
     * 获取系统运行时间
     *
     * @return int
     */
    protected function getUptime(): int
    {
        if (function_exists('sys_getloadavg') && file_exists('/proc/uptime')) {
            $uptime = file_get_contents('/proc/uptime');
            return (int) floatval(explode(' ', $uptime)[0]);
        }

        // 如果无法获取系统uptime，返回应用启动时间
        return time() - filemtime(base_path('bootstrap/app.php'));
    }

    /**
     * 获取内存使用情况
     *
     * @return array
     */
    protected function getMemoryUsage(): array
    {
        $used = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        $limit = $this->parseMemoryLimit(ini_get('memory_limit'));

        return [
            'used' => $this->formatBytes($used),
            'peak' => $this->formatBytes($peak),
            'limit' => $this->formatBytes($limit),
            'percentage' => $limit > 0 ? round(($used / $limit) * 100, 2) : 0
        ];
    }

    /**
     * 获取磁盘使用情况
     *
     * @return array
     */
    protected function getDiskUsage(): array
    {
        $path = storage_path();
        $total = disk_total_space($path);
        $free = disk_free_space($path);
        $used = $total - $free;

        return [
            'used' => $this->formatBytes($used),
            'free' => $this->formatBytes($free),
            'total' => $this->formatBytes($total),
            'percentage' => $total > 0 ? round(($used / $total) * 100, 2) : 0
        ];
    }

    /**
     * 获取缓存状态
     *
     * @return array
     */
    protected function getCacheStatus(): array
    {
        try {
            $cacheDriver = config('cache.default');
            $enabled = $cacheDriver !== 'array';

            return [
                'enabled' => $enabled,
                'driver' => $cacheDriver,
                'status' => $enabled ? 'active' : 'disabled'
            ];
        } catch (\Exception $e) {
            return [
                'enabled' => false,
                'driver' => 'unknown',
                'status' => 'error'
            ];
        }
    }

    /**
     * 获取镜像服务状态
     *
     * @return array
     */
    protected function getMirrorStatus(): array
    {
        try {
            $mirrors = [
                'php' => $this->checkMirrorStatus('php'),
                'pecl' => $this->checkMirrorStatus('pecl'),
                'composer' => $this->checkMirrorStatus('composer')
            ];

            $totalMirrors = count($mirrors);
            $activeMirrors = count(array_filter($mirrors, fn($m) => $m['status'] === 'active'));

            return [
                'total' => $totalMirrors,
                'active' => $activeMirrors,
                'services' => $mirrors
            ];
        } catch (\Exception $e) {
            return [
                'total' => 0,
                'active' => 0,
                'services' => [],
                'error' => 'Failed to check mirror status'
            ];
        }
    }

    /**
     * 检查单个镜像服务状态
     *
     * @param string $type
     * @return array
     */
    protected function checkMirrorStatus(string $type): array
    {
        try {
            $config = $this->configService->getMirrorConfig($type);
            $lastSync = $this->mirrorService->getLastSyncTime($type);

            return [
                'status' => $config['enabled'] ?? false ? 'active' : 'disabled',
                'last_sync' => $lastSync ? $lastSync->toISOString() : null,
                'sync_status' => $this->getSyncStatus($type),
                'file_count' => $this->getFileCount($type),
                'size' => $this->getMirrorSize($type)
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 获取数据库状态
     *
     * @return array
     */
    protected function getDatabaseStatus(): array
    {
        try {
            $connection = DB::connection();
            $pdo = $connection->getPdo();

            return [
                'status' => 'connected',
                'driver' => $connection->getDriverName(),
                'version' => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
                'database' => $connection->getDatabaseName()
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 获取系统负载
     *
     * @return array|null
     */
    protected function getLoadAverage(): ?array
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return [
                '1min' => round($load[0], 2),
                '5min' => round($load[1], 2),
                '15min' => round($load[2], 2)
            ];
        }

        return null;
    }

    /**
     * 格式化字节数
     *
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * 解析内存限制
     *
     * @param string $limit
     * @return int
     */
    protected function parseMemoryLimit(string $limit): int
    {
        if ($limit === '-1') {
            return 0; // 无限制
        }

        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    /**
     * 获取同步状态
     *
     * @param string $type
     * @return string
     */
    protected function getSyncStatus(string $type): string
    {
        // 这里可以检查同步任务状态
        return 'idle'; // idle, syncing, error
    }

    /**
     * 获取文件数量
     *
     * @param string $type
     * @return int
     */
    protected function getFileCount(string $type): int
    {
        // 这里可以统计镜像文件数量
        return 0;
    }

    /**
     * 获取镜像大小
     *
     * @param string $type
     * @return string
     */
    protected function getMirrorSize(string $type): string
    {
        // 这里可以计算镜像占用空间
        return '0 B';
    }
}
