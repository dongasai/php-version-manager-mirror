<?php

namespace Mirror\Utils;

/**
 * 镜像工具类
 */
class MirrorUtils
{
    /**
     * 格式化文件大小
     *
     * @param int $size 文件大小（字节）
     * @return string 格式化后的大小
     */
    public static function formatSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * 获取MIME类型
     *
     * @param string $filename 文件名
     * @return string MIME类型
     */
    public static function getMimeType($filename)
    {
        $mimeTypes = [
            'tar.gz' => 'application/gzip',
            'tgz' => 'application/gzip',
            'phar' => 'application/octet-stream',
            'zip' => 'application/zip',
            'json' => 'application/json',
            'txt' => 'text/plain',
            'html' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
        ];
        
        $extension = '';
        if (preg_match('/\.([^.]+)$/', $filename, $matches)) {
            $extension = $matches[1];
        } elseif (preg_match('/\.tar\.gz$/', $filename)) {
            $extension = 'tar.gz';
        }
        
        return isset($mimeTypes[$extension]) ? $mimeTypes[$extension] : 'application/octet-stream';
    }

    /**
     * 下载文件
     *
     * @param string $url 文件URL
     * @param string $destination 目标路径
     * @return bool 是否下载成功
     */
    public static function downloadFile($url, $destination)
    {
        // 创建目录
        $dir = dirname($destination);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // 使用 cURL 下载文件
        $ch = curl_init($url);
        $fp = fopen($destination, 'w');
        
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $success = curl_exec($ch);
        
        if ($success === false) {
            echo "  下载失败: " . curl_error($ch) . "\n";
            unlink($destination);
        }
        
        curl_close($ch);
        fclose($fp);
        
        return $success;
    }

    /**
     * 获取版本范围
     *
     * @param string $minVersion 最小版本
     * @param string $maxVersion 最大版本
     * @return array 版本列表
     */
    public static function getVersionRange($minVersion, $maxVersion)
    {
        // 简单实现，仅返回最小和最大版本
        // 实际应用中，可以通过 API 获取所有版本
        return [$minVersion, $maxVersion];
    }

    /**
     * 记录日志
     *
     * @param string $message 日志消息
     * @param string $level 日志级别
     */
    public static function log($message, $level = 'info')
    {
        $logDir = ROOT_DIR . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/mirror.log';
        $date = date('Y-m-d H:i:s');
        $logMessage = "[$date] [$level] $message" . PHP_EOL;
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
