<?php

/**
 * PHP 内置服务器路由文件
 *
 * 优化版：直接提供静态文件服务，避免PHP处理文件下载
 */

// 获取请求的文件路径
$requestedFile = $_SERVER['REQUEST_URI'];
$parsedUrl = parse_url($requestedFile);
$path = $parsedUrl['path'];

// 移除查询参数，只保留路径
$cleanPath = $path;

// 检查是否是静态资源请求（CSS, JS, 图片等）
$staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 'woff', 'woff2', 'ttf', 'eot'];
$pathInfo = pathinfo($cleanPath);
$extension = isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : '';

// 如果是静态资源，检查文件是否存在于 public 目录
if (in_array($extension, $staticExtensions)) {
    $staticFile = __DIR__ . $cleanPath;
    if (file_exists($staticFile)) {
        // 让 PHP 内置服务器处理静态文件
        return false;
    }
}

// 检查是否是下载文件请求（直接从data目录提供文件）
if (strpos($cleanPath, '/php/') === 0 || strpos($cleanPath, '/pecl/') === 0 ||
    strpos($cleanPath, '/extensions/') === 0 || strpos($cleanPath, '/composer/') === 0) {

    $dataFile = dirname(__DIR__) . '/data' . $cleanPath;
    if (file_exists($dataFile) && is_file($dataFile)) {
        // 直接发送文件，不经过PHP应用处理
        $mimeType = 'application/octet-stream';

        // 根据文件扩展名设置正确的MIME类型
        $extension = strtolower(pathinfo($dataFile, PATHINFO_EXTENSION));
        switch ($extension) {
            case 'gz':
            case 'tgz':
                $mimeType = 'application/gzip';
                break;
            case 'phar':
                $mimeType = 'application/x-php';
                break;
            case 'zip':
                $mimeType = 'application/zip';
                break;
        }

        // 设置响应头
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($dataFile));
        header('Content-Disposition: attachment; filename="' . basename($dataFile) . '"');

        // 直接输出文件内容
        readfile($dataFile);
        exit;
    }
}

// 对于其他请求（API、Web界面等），路由到 index.php
require_once __DIR__ . '/index.php';
