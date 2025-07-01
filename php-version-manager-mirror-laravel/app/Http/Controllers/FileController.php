<?php

namespace App\Http\Controllers;

use App\Services\MirrorService;
use App\Services\ConfigService;
use App\Models\AccessLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
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
     * 处理文件请求
     *
     * @param Request $request
     * @param string $path
     * @return \Illuminate\Http\Response|\Illuminate\View\View
     */
    public function handle(Request $request, string $path = '')
    {
        $startTime = microtime(true);

        // 记录访问日志
        $this->logAccess($request, $path, $startTime);

        // 获取文件信息
        $fileInfo = $this->mirrorService->handleFileRequest($path);

        if (!$fileInfo) {
            // 检查是否为目录
            $directoryListing = $this->mirrorService->getDirectoryListing($path);

            if ($directoryListing !== null) {
                return $this->showDirectoryListing($path, $directoryListing);
            }

            // 文件不存在，返回404
            return $this->show404($path);
        }

        // 发送文件
        return $this->sendFile($fileInfo, $request);
    }

    /**
     * 显示目录列表
     *
     * @param string $path 目录路径
     * @param array $items 目录项
     * @return \Illuminate\View\View
     */
    protected function showDirectoryListing(string $path, array $items)
    {
        // 构建面包屑导航
        $breadcrumbs = $this->buildBreadcrumbs($path);

        // 确定活动页面
        $activePage = $this->getActivePageFromPath($path);

        // 处理版本筛选
        $filteredItems = $this->applyVersionFilter($items, request('version'));

        return view('directory', [
            'path' => $path,
            'items' => $filteredItems['items'],
            'breadcrumbs' => $breadcrumbs,
            'activePage' => $activePage,
            'filterApplied' => $filteredItems['filterApplied'],
            'filterDescription' => $filteredItems['filterDescription'],
            'queryParams' => request()->query(),
        ]);
    }

    /**
     * 发送文件
     *
     * @param array $fileInfo 文件信息
     * @param Request $request 请求对象
     * @return \Illuminate\Http\Response
     */
    protected function sendFile(array $fileInfo, Request $request)
    {
        $filePath = $fileInfo['path'];
        $fileSize = $fileInfo['size'];
        $mimeType = $fileInfo['mime_type'];
        $lastModified = $fileInfo['last_modified'];

        // 检查If-Modified-Since头
        if ($request->hasHeader('If-Modified-Since')) {
            $ifModifiedSince = strtotime($request->header('If-Modified-Since'));
            if ($ifModifiedSince >= $lastModified) {
                return response('', 304);
            }
        }

        // 设置响应头
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Length' => $fileSize,
            'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"',
            'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            'Cache-Control' => 'public, max-age=3600',
            'ETag' => '"' . md5($filePath . $lastModified) . '"',
        ];

        // 支持断点续传
        if ($request->hasHeader('Range')) {
            return $this->sendPartialFile($filePath, $fileSize, $request->header('Range'), $headers);
        }

        // 发送完整文件
        return response()->file($filePath, $headers);
    }

    /**
     * 发送部分文件（断点续传）
     *
     * @param string $filePath 文件路径
     * @param int $fileSize 文件大小
     * @param string $range Range头
     * @param array $headers 响应头
     * @return \Illuminate\Http\Response
     */
    protected function sendPartialFile(string $filePath, int $fileSize, string $range, array $headers)
    {
        // 解析Range头
        if (!preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            return response('Invalid Range', 416);
        }

        $start = (int) $matches[1];
        $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

        // 验证范围
        if ($start > $end || $start >= $fileSize || $end >= $fileSize) {
            return response('Range Not Satisfiable', 416);
        }

        $length = $end - $start + 1;

        // 设置部分内容响应头
        $headers['Content-Range'] = "bytes {$start}-{$end}/{$fileSize}";
        $headers['Content-Length'] = $length;

        // 读取文件部分内容
        $handle = fopen($filePath, 'rb');
        fseek($handle, $start);
        $content = fread($handle, $length);
        fclose($handle);

        return response($content, 206, $headers);
    }

    /**
     * 构建面包屑导航
     *
     * @param string $path 路径
     * @return array
     */
    protected function buildBreadcrumbs(string $path): array
    {
        $breadcrumbs = [['name' => '首页', 'path' => '/']];

        if (empty($path)) {
            return $breadcrumbs;
        }

        $parts = explode('/', trim($path, '/'));
        $currentPath = '';

        foreach ($parts as $part) {
            $currentPath .= '/' . $part;
            $breadcrumbs[] = [
                'name' => $part,
                'path' => $currentPath,
            ];
        }

        return $breadcrumbs;
    }

    /**
     * 根据路径确定活动页面
     *
     * @param string $path 路径
     * @return string
     */
    protected function getActivePageFromPath(string $path): string
    {
        if (str_starts_with($path, 'php')) {
            return 'php';
        } elseif (str_starts_with($path, 'pecl')) {
            return 'pecl';
        } elseif (str_starts_with($path, 'extensions')) {
            return 'extensions';
        } elseif (str_starts_with($path, 'composer')) {
            return 'composer';
        }

        return 'home';
    }

    /**
     * 应用版本筛选
     *
     * @param array $items 目录项
     * @param string|null $versionFilter 版本筛选
     * @return array
     */
    protected function applyVersionFilter(array $items, ?string $versionFilter): array
    {
        if (!$versionFilter) {
            return [
                'items' => $items,
                'filterApplied' => false,
                'filterDescription' => '',
            ];
        }

        $filteredItems = array_filter($items, function ($item) use ($versionFilter) {
            return str_contains($item['name'], $versionFilter);
        });

        return [
            'items' => $filteredItems,
            'filterApplied' => true,
            'filterDescription' => "版本筛选: {$versionFilter}",
        ];
    }

    /**
     * 显示404页面
     *
     * @param string $path 请求路径
     * @return \Illuminate\Http\Response
     */
    protected function show404(string $path)
    {
        return response()->view('errors.404', [
            'path' => $path,
        ], 404);
    }

    /**
     * 记录访问日志
     *
     * @param Request $request 请求对象
     * @param string $path 请求路径
     * @param float $startTime 开始时间
     * @return void
     */
    protected function logAccess(Request $request, string $path, float $startTime): void
    {
        try {
            AccessLog::logAccess([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status_code' => 200, // 默认200，实际状态码会在响应时更新
                'response_time' => microtime(true) - $startTime,
                'referer' => $request->header('referer'),
                'accessed_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::warning('访问日志记录失败', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);
        }
    }
}
