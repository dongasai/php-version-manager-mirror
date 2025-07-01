<?php

namespace App\Http\Controllers;

use App\Models\Mirror;
use App\Models\SyncJob;
use App\Services\MirrorService;
use App\Services\ConfigService;
use App\Services\CacheService;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    /**
     * 镜像服务
     *
     * @var MirrorService
     */
    protected $mirrorService;

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
     * 构造函数
     *
     * @param MirrorService $mirrorService
     * @param ConfigService $configService
     * @param CacheService $cacheService
     */
    public function __construct(
        MirrorService $mirrorService,
        ConfigService $configService,
        CacheService $cacheService
    ) {
        $this->mirrorService = $mirrorService;
        $this->configService = $configService;
        $this->cacheService = $cacheService;
    }

    /**
     * 显示状态页面
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 获取镜像状态
        $mirrors = Mirror::with('latestSyncJob')->get();
        $mirrorStats = [];

        foreach ($mirrors as $mirror) {
            $stats = $this->mirrorService->getMirrorStats($mirror);
            $mirrorStats[] = [
                'mirror' => $mirror,
                'stats' => $stats,
                'latest_job' => $mirror->latestSyncJob,
            ];
        }

        // 获取同步任务统计
        $jobStats = [
            'total' => SyncJob::count(),
            'running' => SyncJob::running()->count(),
            'completed' => SyncJob::completed()->count(),
            'failed' => SyncJob::failed()->count(),
        ];

        // 获取系统信息
        $systemInfo = [
            'data_dir' => $this->configService->getDataDir(),
            'cache_dir' => $this->configService->getCacheDir(),
            'cache_stats' => $this->cacheService->getCacheStats(),
            'server_config' => $this->configService->getServerConfig(),
        ];

        return view('status', [
            'mirrorStats' => $mirrorStats,
            'jobStats' => $jobStats,
            'systemInfo' => $systemInfo,
        ]);
    }

    /**
     * Ping测速端点
     *
     * @return \Illuminate\Http\Response
     */
    public function ping()
    {
        $startTime = microtime(true);

        // 获取基本状态信息
        $status = [
            'server' => 'pvm-mirror-laravel',
            'version' => '2.0.0',
            'timestamp' => time(),
            'datetime' => now()->format('Y-m-d H:i:s'),
            'timezone' => config('app.timezone'),
            'status' => 'online',
        ];

        try {
            // 获取镜像统计
            $mirrorCount = Mirror::enabled()->count();
            $jobCount = SyncJob::running()->count();

            $status['mirrors'] = $mirrorCount;
            $status['running_jobs'] = $jobCount;
        } catch (\Exception $e) {
            $status['status'] = 'limited';
            $status['error'] = 'Status check failed';
        }

        // 计算响应时间
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
        $status['response_time'] = $responseTime . 'ms';

        return response($status)
            ->header('Content-Type', 'text/plain; charset=utf-8');
    }
}
