<?php

namespace Mirror\Web;

use Mirror\Cache\CacheManager;
use Mirror\Config\MirrorConfig;
use Mirror\Mirror\MirrorStatus;
use Mirror\Resource\ResourceManager;
use Mirror\Utils\MirrorUtils;
use Mirror\Security\AccessControl;

/**
 * Web控制器类
 */
class Controller
{
    /**
     * 配置管理器
     *
     * @var MirrorConfig
     */
    private $config;

    /**
     * 镜像状态管理器
     *
     * @var MirrorStatus
     */
    private $status;

    /**
     * 配置管理器
     *
     * @var \Mirror\Config\ConfigManager
     */
    private $configManager;

    /**
     * 访问控制
     *
     * @var AccessControl
     */
    private $accessControl;

    /**
     * 缓存管理器
     *
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * 资源管理器
     *
     * @var ResourceManager
     */
    private $resourceManager;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->configManager = new \Mirror\Config\ConfigManager();
        $this->config = new MirrorConfig();
        $this->status = new MirrorStatus();
        $this->accessControl = new AccessControl();
        $this->cacheManager = new CacheManager();
        $this->resourceManager = new ResourceManager();
    }

    /**
     * 处理请求
     *
     * @param string $requestPath 请求路径
     */
    public function handleRequest($requestPath)
    {
        // 获取请求方法
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';

        // 获取客户端IP
        $clientIp = $this->getClientIp();

        // 检查访问权限
        if (!$this->accessControl->checkAccess($method, $requestPath)) {
            $this->accessControl->handleAccessDenied($method, $requestPath);
            return;
        }

        // 检查IP请求频率
        if (!$this->resourceManager->checkIpRequestRate($clientIp)) {
            $this->handleRateLimitExceeded($clientIp, $method, $requestPath);
            return;
        }

        // 设置内容类型
        header('Content-Type: text/html; charset=utf-8');

        // 如果是根路径，显示首页
        if ($requestPath === '/' || $requestPath === '/index.php') {
            $this->showHomePage();
            return;
        }

        // 如果是状态页面
        if ($requestPath === '/status/' || $requestPath === '/status') {
            $this->showStatusPage();
            return;
        }

        // 如果是文档页面
        if ($requestPath === '/docs/' || $requestPath === '/docs') {
            $this->showDocsPage();
            return;
        }

        // 如果是ping测速端点
        if ($requestPath === '/ping' || $requestPath === '/ping/') {
            $this->handlePingRequest();
            return;
        }

        // 处理文件下载请求
        $this->handleFileRequest($requestPath);
    }

    /**
     * 显示首页
     */
    public function showHomePage()
    {
        // 获取镜像状态
        $status = $this->status->getStatus();

        // 获取配置
        $mirrorConfig = $this->config->getConfig();
        $serverConfig = $this->configManager->getServerConfig();

        // 构建模板需要的配置数据结构
        $config = $this->buildTemplateConfig($mirrorConfig, $serverConfig);

        // 渲染首页模板
        $view = new View();
        $view->setLayout('layout')
             ->setActivePage('home');

        $view->render('home', [
            'title' => 'PVM 下载站 - 首页',
            'page_title' => 'PHP 版本管理器下载站',
            'use_container' => true,
            'status' => $status,
            'config' => $config,
            'formatSize' => function($size) {
                return MirrorUtils::formatSize($size);
            }
        ]);
    }

    /**
     * 处理文件下载请求
     *
     * @param string $requestPath 请求路径
     */
    public function handleFileRequest($requestPath)
    {
        // 解析请求路径
        $path = parse_url($requestPath, PHP_URL_PATH);

        // 移除前导斜杠
        $path = ltrim($path, '/');

        // 检查路径是否为 API 请求
        if (strpos($path, 'api/') === 0) {
            $this->handleApiRequest($path);
            return;
        }

        // 构建文件路径
        $filePath = ROOT_DIR . '/data/' . $path;

        // 如果是目录，显示目录列表
        if (is_dir($filePath)) {
            $this->showDirectoryListing($path, $filePath);
            return;
        }

        // 如果文件不存在，返回 404
        if (!file_exists($filePath)) {
            $this->show404();
            return;
        }

        // 验证文件完整性
        if (!$this->validateFileIntegrity($filePath)) {
            $this->handleCorruptedFile($filePath);
            return;
        }

        // 检查是否可以开始新的下载
        if (!$this->resourceManager->canStartDownload()) {
            $this->handleDownloadLimitExceeded();
            return;
        }

        // 开始下载
        $this->resourceManager->startDownload();

        // 获取下载速度限制
        $speedLimit = $this->resourceManager->getDownloadSpeedLimit();

        // 发送文件
        $contentType = MirrorUtils::getMimeType($filePath);
        $fileSize = filesize($filePath);

        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . $fileSize);

        // 如果没有速度限制，直接发送文件
        if ($speedLimit <= 0) {
            readfile($filePath);
        } else {
            // 使用分块传输和速度限制
            $this->sendFileWithSpeedLimit($filePath, $speedLimit);
        }

        // 结束下载
        $this->resourceManager->endDownload();
    }

    /**
     * 显示目录列表
     *
     * @param string $path 请求路径
     * @param string $filePath 文件系统路径
     */
    public function showDirectoryListing($path, $filePath)
    {
        // 获取目录内容
        $files = scandir($filePath);

        // 过滤掉 . 和 ..
        $files = array_filter($files, function($file) {
            return $file !== '.' && $file !== '..';
        });

        // 获取URL参数
        $queryParams = [];
        if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $queryParams);
        }

        // 处理版本筛选
        $filteredFiles = $files;
        $filterApplied = false;
        $filterDescription = '';

        if (isset($queryParams['version']) && !empty($queryParams['version'])) {
            $versionFilter = $queryParams['version'];
            $filterApplied = true;

            // 根据不同目录类型应用不同的筛选逻辑
            if (strpos($path, 'php') === 0) {
                // 筛选PHP版本
                $filteredFiles = array_filter($files, function($file) use ($versionFilter) {
                    return preg_match('/php-' . preg_quote($versionFilter, '/') . '(\.|-)/', $file);
                });
                $filterDescription = "PHP {$versionFilter}.x 版本";
            } elseif (strpos($path, 'pecl') === 0) {
                // 筛选PECL扩展版本
                $filteredFiles = array_filter($files, function($file) use ($versionFilter) {
                    return strpos($file, "-{$versionFilter}.") !== false;
                });
                $filterDescription = "PHP {$versionFilter} 兼容的PECL扩展";
            } elseif (strpos($path, 'extensions') === 0) {
                // 筛选扩展版本
                $filteredFiles = array_filter($files, function($file) use ($versionFilter) {
                    return strpos($file, "-{$versionFilter}.") !== false;
                });
                $filterDescription = "PHP {$versionFilter} 兼容的扩展";
            }
        }

        // 处理扩展名筛选
        if (isset($queryParams['ext']) && !empty($queryParams['ext'])) {
            $extFilter = $queryParams['ext'];
            $filterApplied = true;

            $filteredFiles = array_filter($filteredFiles, function($file) use ($extFilter) {
                return pathinfo($file, PATHINFO_EXTENSION) === $extFilter;
            });

            $filterDescription .= ($filterDescription ? '，' : '') . "扩展名: {$extFilter}";
        }

        // 处理名称搜索
        if (isset($queryParams['search']) && !empty($queryParams['search'])) {
            $searchFilter = $queryParams['search'];
            $filterApplied = true;

            $filteredFiles = array_filter($filteredFiles, function($file) use ($searchFilter) {
                return stripos($file, $searchFilter) !== false;
            });

            $filterDescription .= ($filterDescription ? '，' : '') . "搜索: {$searchFilter}";
        }

        // 构建面包屑导航
        $breadcrumbs = [];
        $parts = explode('/', $path);
        $currentPath = '';

        foreach ($parts as $part) {
            if (empty($part)) continue;

            $currentPath .= $part . '/';
            $breadcrumbs[] = [
                'name' => $part,
                'path' => '/' . $currentPath,
            ];
        }

        // 确定活动页面
        $activePage = 'home';
        if (strpos($path, 'php') === 0) {
            $activePage = 'php';
        } elseif (strpos($path, 'pecl') === 0) {
            $activePage = 'pecl';
        } elseif (strpos($path, 'extensions') === 0) {
            $activePage = 'extensions';
        } elseif (strpos($path, 'composer') === 0) {
            $activePage = 'composer';
        }

        // 构建页面标题
        $pageTitle = '目录列表: /' . $path;
        if ($filterApplied) {
            $pageTitle .= ' (' . $filterDescription . ')';
        }

        // 渲染目录列表模板
        $view = new View();
        $view->setLayout('layout')
             ->setActivePage($activePage);

        $view->render('directory', [
            'title' => $pageTitle,
            'page_title' => $pageTitle,
            'use_container' => true,
            'show_breadcrumb' => true,
            'path' => $path,
            'breadcrumbs' => $breadcrumbs,
            'files' => $filterApplied ? $filteredFiles : $files,
            'filePath' => $filePath,
            'filterApplied' => $filterApplied,
            'filterDescription' => $filterDescription,
            'queryParams' => $queryParams,
            'formatSize' => function($size) {
                return MirrorUtils::formatSize($size);
            }
        ]);
    }

    /**
     * 处理API请求
     *
     * @param string $path API路径
     */
    public function handleApiRequest($path)
    {
        // 设置内容类型为 JSON
        header('Content-Type: application/json');

        // 解析 API 路径
        $apiPath = substr($path, 4); // 移除 'api/'

        // 移除 .json 后缀（如果存在）
        if (substr($apiPath, -5) === '.json') {
            $apiPath = substr($apiPath, 0, -5);
        }

        // 获取缓存配置
        $cacheConfig = $this->configManager->getCacheConfig();
        $cacheTags = isset($cacheConfig['cache_tags']) ? $cacheConfig['cache_tags'] : [];
        $defaultTtl = isset($cacheConfig['default_ttl']) ? $cacheConfig['default_ttl'] : 3600;

        // 根据 API 路径返回不同的数据
        switch ($apiPath) {
            case 'status':
                // 检查是否启用状态缓存
                if ($this->cacheManager->isEnabled() && (isset($cacheTags['status']) ? $cacheTags['status'] : false)) {
                    $cacheKey = 'api_status';
                    $data = $this->cacheManager->get($cacheKey);
                    if ($data === null) {
                        $data = $this->status->getStatus();
                        $this->cacheManager->set($cacheKey, $data, $defaultTtl);
                    }
                } else {
                    $data = $this->status->getStatus();
                }
                echo json_encode($data);
                break;

            case 'php':
                // 检查是否启用PHP缓存
                if ($this->cacheManager->isEnabled() && (isset($cacheTags['php']) ? $cacheTags['php'] : false)) {
                    $cacheKey = 'api_php';
                    $data = $this->cacheManager->get($cacheKey);
                    if ($data === null) {
                        $data = $this->status->getPhpList();
                        $this->cacheManager->set($cacheKey, $data, $defaultTtl);
                    }
                } else {
                    $data = $this->status->getPhpList();
                }
                echo json_encode($data);
                break;

            case 'pecl':
                // 检查是否启用PECL缓存
                if ($this->cacheManager->isEnabled() && (isset($cacheTags['pecl']) ? $cacheTags['pecl'] : false)) {
                    $cacheKey = 'api_pecl';
                    $data = $this->cacheManager->get($cacheKey);
                    if ($data === null) {
                        $data = $this->status->getPeclList();
                        $this->cacheManager->set($cacheKey, $data, $defaultTtl);
                    }
                } else {
                    $data = $this->status->getPeclList();
                }
                echo json_encode($data);
                break;

            case 'extensions':
                // 检查是否启用扩展缓存
                if ($this->cacheManager->isEnabled() && (isset($cacheTags['extensions']) ? $cacheTags['extensions'] : false)) {
                    $cacheKey = 'api_extensions';
                    $data = $this->cacheManager->get($cacheKey);
                    if ($data === null) {
                        $data = $this->status->getExtensionsList();
                        $this->cacheManager->set($cacheKey, $data, $defaultTtl);
                    }
                } else {
                    $data = $this->status->getExtensionsList();
                }
                echo json_encode($data);
                break;

            case 'composer':
                // 检查是否启用Composer缓存
                if ($this->cacheManager->isEnabled() && (isset($cacheTags['composer']) ? $cacheTags['composer'] : false)) {
                    $cacheKey = 'api_composer';
                    $data = $this->cacheManager->get($cacheKey);
                    if ($data === null) {
                        $data = $this->status->getComposerList();
                        $this->cacheManager->set($cacheKey, $data, $defaultTtl);
                    }
                } else {
                    $data = $this->status->getComposerList();
                }
                echo json_encode($data);
                break;

            default:
                header('HTTP/1.0 404 Not Found');
                echo json_encode(['error' => 'API not found']);
                break;
        }
    }

    /**
     * 显示状态页面
     */
    public function showStatusPage()
    {
        // 获取缓存配置
        $cacheConfig = $this->configManager->getCacheConfig();
        $cacheTags = isset($cacheConfig['cache_tags']) ? $cacheConfig['cache_tags'] : [];
        $defaultTtl = isset($cacheConfig['default_ttl']) ? $cacheConfig['default_ttl'] : 3600;

        // 检查是否启用状态缓存
        $cacheKey = 'status_page';
        $statusData = null;
        $systemData = null;

        if ($this->cacheManager->isEnabled() && (isset($cacheTags['status']) ? $cacheTags['status'] : false)) {
            $statusData = $this->cacheManager->get($cacheKey . '_status');
            $systemData = $this->cacheManager->get($cacheKey . '_system');
        }

        // 如果缓存不存在，则获取数据
        if ($statusData === null) {
            // 获取镜像状态
            $status = $this->status->getStatus();

            // 添加各类型的最后更新时间
            $status['php_last_update'] = $status['last_update'];
            $status['pecl_last_update'] = $status['last_update'];
            $status['extension_last_update'] = $status['last_update'];
            $status['composer_last_update'] = $status['last_update'];

            // 添加各类型的大小
            $status['php_size'] = $status['total_size'] * 0.6; // 假设PHP源码占60%
            $status['pecl_size'] = $status['total_size'] * 0.2; // 假设PECL扩展占20%
            $status['extension_size'] = $status['total_size'] * 0.15; // 假设特定扩展占15%
            $status['composer_size'] = $status['total_size'] * 0.05; // 假设Composer包占5%

            $statusData = $status;

            // 缓存状态数据
            if ($this->cacheManager->isEnabled() && (isset($cacheTags['status']) ? $cacheTags['status'] : false)) {
                $this->cacheManager->set($cacheKey . '_status', $statusData, $defaultTtl);
            }
        }

        // 系统状态数据不缓存太久，因为它会变化
        if ($systemData === null) {
            // 获取系统状态
            $system = [
                'hostname' => php_uname('n'),
                'os' => php_uname('s') . ' ' . php_uname('r'),
                'kernel' => php_uname('v'),
                'php_version' => PHP_VERSION,
                'web_server' => isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown',
                'uptime' => $this->getSystemUptime(),
                'load' => $this->getSystemLoad(),
                'cpu_usage' => rand(10, 90), // 模拟数据
                'memory_usage' => rand(30, 80), // 模拟数据
                'disk_usage' => rand(40, 95), // 模拟数据
            ];

            $systemData = $system;

            // 缓存系统数据（较短时间）
            if ($this->cacheManager->isEnabled() && (isset($cacheTags['status']) ? $cacheTags['status'] : false)) {
                $this->cacheManager->set($cacheKey . '_system', $systemData, 60); // 只缓存1分钟
            }
        }

        // 渲染状态页面
        $view = new View();
        $view->setLayout('layout')
             ->setActivePage('status');

        $view->render('status', [
            'title' => 'PVM 下载站 - 状态监控',
            'page_title' => '镜像状态监控',
            'use_container' => true,
            'status' => $statusData,
            'system' => $systemData,
            'formatSize' => function($size) {
                return MirrorUtils::formatSize($size);
            }
        ]);
    }

    /**
     * 显示文档页面
     */
    public function showDocsPage()
    {
        // 渲染文档页面
        $view = new View();
        $view->setLayout('layout')
             ->setActivePage('docs');

        $view->render('docs', [
            'title' => 'PVM 下载站 - 文档',
            'page_title' => '使用文档',
            'use_container' => true,
            'show_breadcrumb' => true,
            'breadcrumbs' => [
                ['name' => '文档', 'path' => '/docs/']
            ]
        ]);
    }

    /**
     * 获取系统运行时间
     *
     * @return string
     */
    private function getSystemUptime()
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $uptime = @file_get_contents('/proc/uptime');
            if ($uptime !== false) {
                $uptime = (float)explode(' ', $uptime)[0];
                // 显式转换避免隐式转换警告
                $days = (int)floor((float)$uptime / 86400);
                $hours = (int)floor((float)($uptime % 86400) / 3600);
                $minutes = (int)floor((float)($uptime % 3600) / 60);

                return "{$days}天 {$hours}小时 {$minutes}分钟";
            }
        }

        return 'Unknown';
    }

    /**
     * 获取系统负载
     *
     * @return string
     */
    private function getSystemLoad()
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return sprintf('%.2f, %.2f, %.2f', $load[0], $load[1], $load[2]);
        }

        return 'Unknown';
    }

    /**
     * 显示404页面
     */
    public function show404()
    {
        header('HTTP/1.0 404 Not Found');

        $view = new View();
        $view->setLayout('layout')
             ->setActivePage('home');

        $view->render('404', [
            'title' => '404 Not Found',
            'use_container' => true
        ]);
    }

    /**
     * 获取客户端IP
     *
     * @return string
     */
    private function getClientIp()
    {
        // 尝试从各种可能的服务器变量中获取客户端IP
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // HTTP_X_FORWARDED_FOR可能包含多个IP，取第一个
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            return $_SERVER['HTTP_FORWARDED'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '0.0.0.0';
    }

    /**
     * 处理请求频率超限
     *
     * @param string $ip 客户端IP
     * @param string $method 请求方法
     * @param string $uri 请求URI
     */
    private function handleRateLimitExceeded($ip, $method, $uri)
    {
        // 记录访问被拒绝
        if (method_exists($this->accessControl, 'logDenied')) {
            $this->accessControl->logDenied($ip, $method, $uri, 'Rate limit exceeded');
        }

        header('HTTP/1.0 429 Too Many Requests');
        header('Content-Type: text/html; charset=utf-8');
        header('Retry-After: 60');

        echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>429 Too Many Requests</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #d9534f;
        }
    </style>
</head>
<body>
    <h1>429 Too Many Requests</h1>
    <p>您的请求频率过高，请稍后再试。</p>
    <p>您的IP地址: ' . $ip . '</p>
    <p>请等待至少1分钟后再次尝试访问。</p>
</body>
</html>';

        exit;
    }

    /**
     * 处理下载限制超限
     */
    private function handleDownloadLimitExceeded()
    {
        header('HTTP/1.0 503 Service Unavailable');
        header('Content-Type: text/html; charset=utf-8');
        header('Retry-After: 300');

        echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>503 Service Unavailable</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #d9534f;
        }
    </style>
</head>
<body>
    <h1>503 Service Unavailable</h1>
    <p>服务器当前下载任务已满，请稍后再试。</p>
    <p>请等待至少5分钟后再次尝试下载。</p>
</body>
</html>';

        exit;
    }

    /**
     * 使用速度限制发送文件
     *
     * @param string $filePath 文件路径
     * @param int $speedLimit 速度限制（字节/秒）
     */
    private function sendFileWithSpeedLimit($filePath, $speedLimit)
    {
        // 打开文件
        $handle = fopen($filePath, 'rb');
        if ($handle === false) {
            return;
        }

        // 设置缓冲区大小
        $chunkSize = 8192; // 8KB

        // 计算每个块的发送间隔（微秒）
        $sleepTime = (int)(($chunkSize / $speedLimit) * 1000000);

        // 禁用输出缓冲
        if (ob_get_level()) {
            ob_end_clean();
        }

        // 设置无限执行时间
        set_time_limit(0);

        // 分块发送文件
        while (!feof($handle)) {
            // 读取一个块
            $buffer = fread($handle, $chunkSize);
            if ($buffer === false) {
                break;
            }

            // 发送块
            echo $buffer;

            // 刷新输出缓冲
            flush();

            // 如果连接已断开，则停止发送
            if (connection_status() != CONNECTION_NORMAL) {
                break;
            }

            // 等待一段时间，以限制速度
            if ($sleepTime > 0) {
                usleep($sleepTime);
            }
        }

        // 关闭文件
        fclose($handle);
    }

    /**
     * 构建模板需要的配置数据结构
     *
     * @param array $mirrorConfig 镜像配置
     * @param array $serverConfig 服务器配置
     * @return array
     */
    private function buildTemplateConfig($mirrorConfig, $serverConfig)
    {
        // 构建PHP版本配置
        $phpVersions = [];
        if (isset($mirrorConfig['php']) && isset($mirrorConfig['php']['enabled']) && $mirrorConfig['php']['enabled']) {
            // 从实际文件中获取PHP版本
            $phpList = $this->status->getPhpList();
            foreach ($phpList as $majorVersion => $versions) {
                $phpVersions[$majorVersion] = $majorVersion;
            }
        }

        // 构建PECL扩展配置
        $peclExtensions = [];
        if (isset($mirrorConfig['pecl']) && isset($mirrorConfig['pecl']['enabled']) && $mirrorConfig['pecl']['enabled']) {
            $peclExtensionsList = isset($mirrorConfig['pecl']['extensions']) ? $mirrorConfig['pecl']['extensions'] : [];
            // 将索引数组转换为关联数组，扩展名作为键
            foreach ($peclExtensionsList as $extension) {
                $peclExtensions[$extension] = $extension; // 简单的键值对
            }
        }

        // 构建GitHub扩展配置
        $githubExtensions = [];
        if (isset($mirrorConfig['extensions']) && isset($mirrorConfig['extensions']['enabled']) && $mirrorConfig['extensions']['enabled']) {
            $githubExtensionsList = isset($mirrorConfig['extensions']['extensions']) ? $mirrorConfig['extensions']['extensions'] : [];
            // 将索引数组转换为关联数组，扩展名作为键
            foreach ($githubExtensionsList as $extension) {
                $githubExtensions[$extension] = $extension; // 简单的键值对
            }
        }

        // 构建Composer版本配置
        $composerVersions = [];
        if (isset($mirrorConfig['composer']) && isset($mirrorConfig['composer']['enabled']) && $mirrorConfig['composer']['enabled']) {
            // 从实际文件中获取Composer版本
            $composerList = $this->status->getComposerList();
            foreach ($composerList as $item) {
                $composerVersions[] = $item['version'];
            }
        }

        return [
            'php' => [
                'enabled' => isset($mirrorConfig['php']['enabled']) ? $mirrorConfig['php']['enabled'] : false,
                'versions' => $phpVersions,
            ],
            'pecl' => [
                'enabled' => isset($mirrorConfig['pecl']['enabled']) ? $mirrorConfig['pecl']['enabled'] : false,
                'extensions' => $peclExtensions,
            ],
            'extensions' => $githubExtensions,
            'composer' => [
                'enabled' => isset($mirrorConfig['composer']['enabled']) ? $mirrorConfig['composer']['enabled'] : false,
                'versions' => $composerVersions,
            ],
            'server' => $serverConfig,
        ];
    }

    /**
     * 验证文件完整性
     *
     * @param string $filePath 文件路径
     * @return bool 是否完整
     */
    private function validateFileIntegrity($filePath)
    {
        // 获取文件扩展名
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // 检查文件大小
        $fileSize = filesize($filePath);
        if ($fileSize === 0) {
            return false;
        }

        // 根据文件类型进行验证
        switch ($extension) {
            case 'gz':
            case 'tgz':
                return $this->validateGzipFile($filePath);
            case 'tar':
                return $this->validateTarFile($filePath);
            case 'zip':
                return $this->validateZipFile($filePath);
            case 'phar':
                return $this->validatePharFile($filePath);
            default:
                // 对于其他文件类型，只检查基本完整性
                return $this->validateGenericFile($filePath);
        }
    }

    /**
     * 验证 Gzip 文件完整性
     *
     * @param string $filePath 文件路径
     * @return bool 是否完整
     */
    private function validateGzipFile($filePath)
    {
        // 检查文件大小，PHP 源码包应该至少有几MB
        $fileSize = filesize($filePath);
        if ($fileSize < 1024 * 1024) { // 小于1MB的文件很可能是损坏的
            return false;
        }

        // 检查文件头
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 2);
        fclose($handle);

        // 检查 gzip 魔数
        if ($header !== "\x1f\x8b") {
            return false;
        }

        // 使用 gzfile 来验证整个文件的完整性
        // 这比 gzread 更严格，会检查整个文件的完整性
        $lines = @gzfile($filePath);
        if ($lines === false) {
            return false;
        }

        // 检查解压后的内容是否合理
        $totalContent = implode('', $lines);
        if (strlen($totalContent) < 1024 * 100) { // 解压后应该至少有100KB
            return false;
        }

        return true;
    }

    /**
     * 验证 Tar 文件完整性
     *
     * @param string $filePath 文件路径
     * @return bool 是否完整
     */
    private function validateTarFile($filePath)
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        // 检查 tar 文件标识
        fseek($handle, 257);
        $ustar = fread($handle, 5);
        fclose($handle);

        return $ustar === 'ustar';
    }

    /**
     * 验证 ZIP 文件完整性
     *
     * @param string $filePath 文件路径
     * @return bool 是否完整
     */
    private function validateZipFile($filePath)
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 2);
        fclose($handle);

        return $header === 'PK';
    }

    /**
     * 验证 PHAR 文件完整性
     *
     * @param string $filePath 文件路径
     * @return bool 是否完整
     */
    private function validatePharFile($filePath)
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 512);
        fclose($handle);

        // 检查是否为 PHP 文件或二进制 PHAR
        return strpos($header, '<?php') === 0 ||
               strpos($header, '#!/usr/bin/env php') === 0 ||
               substr($header, 0, 2) === 'PK';
    }

    /**
     * 验证通用文件完整性
     *
     * @param string $filePath 文件路径
     * @return bool 是否完整
     */
    private function validateGenericFile($filePath)
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        $header = fread($handle, 512);
        fclose($handle);

        // 检查是否为 HTML 错误页面
        if (stripos($header, '<html') !== false || stripos($header, '<!doctype html') !== false) {
            return false;
        }

        // 检查是否包含错误信息
        $lowerHeader = strtolower($header);
        $errorPatterns = ['not found', '404', 'error', 'forbidden', 'access denied'];

        foreach ($errorPatterns as $pattern) {
            if (strpos($lowerHeader, $pattern) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * 处理损坏文件
     *
     * @param string $filePath 文件路径
     */
    private function handleCorruptedFile($filePath)
    {
        // 记录损坏文件
        error_log("检测到损坏文件: $filePath");

        // 删除损坏文件
        if (file_exists($filePath)) {
            unlink($filePath);
            error_log("已删除损坏文件: $filePath");
        }

        // 返回 404 错误
        header('HTTP/1.0 404 Not Found');
        header('Content-Type: text/html; charset=utf-8');

        echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>文件不可用 - PVM 下载站</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; text-align: center; }
        .error { color: #d32f2f; }
        .info { color: #1976d2; margin-top: 20px; }
    </style>
</head>
<body>
    <h1 class="error">文件不可用</h1>
    <p>请求的文件已损坏或不完整，已被自动删除。</p>
    <div class="info">
        <p>管理员可以：</p>
        <ul style="display: inline-block; text-align: left;">
            <li>重新同步该文件</li>
            <li>检查下载源的可用性</li>
            <li>查看同步日志获取更多信息</li>
        </ul>
    </div>
    <p><a href="/">返回首页</a></p>
</body>
</html>';
    }

    /**
     * 处理ping测速请求
     */
    public function handlePingRequest()
    {
        // 设置内容类型为纯文本
        header('Content-Type: text/plain; charset=utf-8');

        // 记录请求开始时间
        $startTime = microtime(true);

        // 获取服务器基本信息
        $serverInfo = [
            'server' => 'pvm-mirror',
            'version' => '1.0.0',
            'timestamp' => time(),
            'datetime' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
        ];

        // 获取镜像状态（简化版，避免耗时操作）
        try {
            $status = $this->status->getBasicStatus();
            $serverInfo['status'] = 'online';
            $serverInfo['php_versions'] = isset($status['php_count']) ? $status['php_count'] : 0;
            $serverInfo['pecl_extensions'] = isset($status['pecl_count']) ? $status['pecl_count'] : 0;
        } catch (Exception $e) {
            $serverInfo['status'] = 'limited';
            $serverInfo['error'] = 'Status check failed';
        }

        // 计算响应时间
        $responseTime = round((microtime(true) - $startTime) * 1000, 2);
        $serverInfo['response_time_ms'] = $responseTime;

        // 输出ping响应（简单的键值对格式，便于解析）
        echo "pong\n";
        echo "server=pvm-mirror\n";
        echo "version=1.0.0\n";
        echo "status=" . $serverInfo['status'] . "\n";
        echo "timestamp=" . $serverInfo['timestamp'] . "\n";
        echo "datetime=" . $serverInfo['datetime'] . "\n";
        echo "response_time_ms=" . $responseTime . "\n";

        if (isset($serverInfo['php_versions'])) {
            echo "php_versions=" . $serverInfo['php_versions'] . "\n";
        }
        if (isset($serverInfo['pecl_extensions'])) {
            echo "pecl_extensions=" . $serverInfo['pecl_extensions'] . "\n";
        }

        // 添加一个简单的负载指示器
        $load = sys_getloadavg();
        if ($load !== false && isset($load[0])) {
            echo "load_avg=" . round($load[0], 2) . "\n";
        }

        // 内存使用情况
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        echo "memory_usage_mb=" . round($memoryUsage / 1024 / 1024, 2) . "\n";

        // 结束标记
        echo "end\n";
    }
}
