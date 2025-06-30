<?php

/**
 * PVM 镜像 Web 服务
 *
 * 提供 Web 界面和文件下载服务
 */

// 开启错误显示
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 定义根目录
define('ROOT_DIR', dirname(__DIR__));

// 包含自动加载器
require ROOT_DIR . '/srcMirror/Autoloader.php';

// 注册自动加载器
Autoloader::register();

try {
    // 创建控制器
    $controller = new Mirror\Web\Controller();

    // 获取请求路径
    $requestPath = $_SERVER['REQUEST_URI'];

    // 处理请求
    $controller->handleRequest($requestPath);
} catch (Exception $e) {
    // 显示错误信息
    echo '<h1>Error</h1>';
    echo '<p>' . $e->getMessage() . '</p>';
    echo '<h2>Stack Trace</h2>';
    echo '<pre>' . $e->getTraceAsString() . '</pre>';
}
