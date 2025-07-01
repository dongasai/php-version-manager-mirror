<?php

namespace App\Http\Controllers;

use App\Services\MirrorService;
use App\Services\ConfigService;
use Illuminate\Http\Request;

class HomeController extends Controller
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
     * 构造函数
     *
     * @param MirrorService $mirrorService
     * @param ConfigService $configService
     */
    public function __construct(MirrorService $mirrorService, ConfigService $configService)
    {
        $this->mirrorService = $mirrorService;
        $this->configService = $configService;
    }

    /**
     * 显示首页
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 获取所有启用的镜像
        $mirrors = $this->mirrorService->getAllMirrors();

        // 获取镜像统计信息
        $stats = [];
        foreach ($mirrors as $mirror) {
            $mirrorStats = $this->mirrorService->getMirrorStats($mirror);
            $stats[$mirror->type] = [
                'name' => $mirror->name,
                'file_count' => $mirrorStats['file_count'],
                'total_size' => $mirrorStats['total_size'],
                'last_updated' => $mirrorStats['last_updated'],
            ];
        }

        // 获取系统配置
        $serverConfig = $this->configService->getServerConfig();

        return view('home', [
            'mirrors' => $mirrors,
            'stats' => $stats,
            'serverConfig' => $serverConfig,
        ]);
    }
}
