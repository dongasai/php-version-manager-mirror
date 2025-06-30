<?php

namespace Mirror\Utils;

/**
 * 文件工具类
 */
class FileUtils
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
     * 下载文件
     *
     * @param string $url 文件URL
     * @param string $destination 目标路径
     * @param array $options 下载选项
     * @return bool 是否下载成功
     */
    public static function downloadFile($url, $destination, $options = [])
    {
        // 默认选项
        $defaultOptions = [
            'min_size' => 1024,        // 最小文件大小 (1KB)
            'max_retries' => 3,        // 最大重试次数
            'timeout' => 300,          // 超时时间 (秒)
            'verify_content' => true,  // 是否验证内容
            'expected_type' => null,   // 期望的文件类型
        ];

        $options = array_merge($defaultOptions, $options);

        // 创建目录
        $dir = dirname($destination);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $retries = 0;
        while ($retries < $options['max_retries']) {
            $retries++;

            // 尝试下载
            if (self::attemptDownload($url, $destination, $options)) {
                // 验证下载的文件
                if (self::validateDownloadedFile($destination, $options)) {
                    return true;
                } else {
                    echo "  文件验证失败，重试中... (尝试 $retries/{$options['max_retries']})\n";
                    if (file_exists($destination)) {
                        unlink($destination);
                    }
                }
            } else {
                echo "  下载失败，重试中... (尝试 $retries/{$options['max_retries']})\n";
            }

            // 如果不是最后一次重试，等待一段时间
            if ($retries < $options['max_retries']) {
                sleep(2);
            }
        }

        echo "  下载失败: 已达到最大重试次数\n";
        return false;
    }

    /**
     * 尝试下载文件
     *
     * @param string $url 文件URL
     * @param string $destination 目标路径
     * @param array $options 下载选项
     * @return bool 是否下载成功
     */
    private static function attemptDownload($url, $destination, $options)
    {
        // 使用 cURL 下载文件
        $ch = curl_init($url);
        $fp = fopen($destination, 'w');

        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout']);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PVM-Mirror/1.0');

        // 添加进度回调
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function($resource, $downloadSize, $downloaded, $uploadSize, $uploaded) {
            if ($downloadSize > 0) {
                $percent = round(($downloaded / $downloadSize) * 100, 1);
                echo "\r  下载进度: {$percent}% (" . self::formatSize($downloaded) . "/" . self::formatSize($downloadSize) . ")";
            }
        });

        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);
        fclose($fp);

        echo "\n"; // 换行

        if ($success === false) {
            echo "  cURL 错误: $error\n";
            if (file_exists($destination)) {
                unlink($destination);
            }
            return false;
        }

        if ($httpCode >= 400) {
            echo "  HTTP 错误: $httpCode\n";
            if (file_exists($destination)) {
                unlink($destination);
            }
            return false;
        }

        return true;
    }

    /**
     * 验证下载的文件
     *
     * @param string $filePath 文件路径
     * @param array $options 验证选项
     * @return bool 是否验证通过
     */
    private static function validateDownloadedFile($filePath, $options)
    {
        if (!file_exists($filePath)) {
            echo "  验证失败: 文件不存在\n";
            return false;
        }

        // 检查文件大小
        $fileSize = filesize($filePath);
        if ($fileSize < $options['min_size']) {
            echo "  验证失败: 文件太小 (" . self::formatSize($fileSize) . " < " . self::formatSize($options['min_size']) . ")\n";
            return false;
        }

        // 检查文件是否为空或只包含空白字符
        if ($fileSize === 0) {
            echo "  验证失败: 文件为空\n";
            return false;
        }

        // 验证文件内容
        if ($options['verify_content']) {
            if (!self::verifyFileContent($filePath, $options)) {
                return false;
            }
        }

        echo "  文件验证通过: " . self::formatSize($fileSize) . "\n";
        return true;
    }

    /**
     * 验证文件内容
     *
     * @param string $filePath 文件路径
     * @param array $options 验证选项
     * @return bool 是否验证通过
     */
    private static function verifyFileContent($filePath, $options)
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        // 读取文件头部用于验证
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            echo "  验证失败: 无法读取文件\n";
            return false;
        }

        $header = fread($handle, 512);
        fclose($handle);

        // 检查是否为HTML错误页面
        if (stripos($header, '<html') !== false || stripos($header, '<!doctype html') !== false) {
            echo "  验证失败: 下载的是HTML页面，可能是404错误页面\n";
            return false;
        }

        // 根据文件扩展名验证文件格式
        switch ($extension) {
            case 'gz':
            case 'tgz':
                return self::verifyGzipFile($filePath, $header);

            case 'tar':
                return self::verifyTarFile($filePath, $header);

            case 'zip':
                return self::verifyZipFile($filePath, $header);

            case 'phar':
                return self::verifyPharFile($filePath, $header);

            default:
                // 对于未知类型，只检查是否包含二进制内容或合理的文本内容
                return self::verifyGenericFile($filePath, $header);
        }
    }

    /**
     * 验证 Gzip 文件
     *
     * @param string $filePath 文件路径
     * @param string $header 文件头部
     * @return bool 是否验证通过
     */
    private static function verifyGzipFile($filePath, $header)
    {
        // Gzip 文件的魔数是 1f 8b
        if (substr($header, 0, 2) !== "\x1f\x8b") {
            echo "  验证失败: 不是有效的 Gzip 文件\n";
            return false;
        }

        // 检查文件大小，gzip 文件至少应该有完整的头部和尾部
        $fileSize = filesize($filePath);
        if ($fileSize < 18) { // gzip 最小大小：10字节头部 + 8字节尾部
            echo "  验证失败: Gzip 文件太小，可能不完整\n";
            return false;
        }

        // 尝试完整解压缩测试，而不是只读取部分数据
        $tempFile = tempnam(sys_get_temp_dir(), 'gzip_test_');
        $success = false;

        try {
            // 只验证文件头部和少量内容，避免解压整个大文件
            $gz = gzopen($filePath, 'rb');
            if (!$gz) {
                echo "  验证失败: 无法打开 Gzip 文件\n";
                return false;
            }

            // 尝试读取前几KB数据来验证文件完整性
            $testData = gzread($gz, 8192); // 读取8KB
            gzclose($gz);

            if ($testData === false) {
                echo "  验证失败: Gzip 文件损坏或不完整\n";
                return false;
            }

            if (strlen($testData) === 0) {
                echo "  验证失败: 解压后文件为空\n";
                return false;
            }

            $success = true;

        } catch (Exception $e) {
            echo "  验证失败: Gzip 解压异常 - " . $e->getMessage() . "\n";
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }

        return $success;
    }

    /**
     * 验证 Tar 文件
     *
     * @param string $filePath 文件路径
     * @param string $header 文件头部
     * @return bool 是否验证通过
     */
    private static function verifyTarFile($filePath, $header)
    {
        // Tar 文件在偏移 257 处有 "ustar" 标识
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        fseek($handle, 257);
        $ustar = fread($handle, 5);
        fclose($handle);

        if ($ustar !== 'ustar') {
            echo "  验证失败: 不是有效的 Tar 文件\n";
            return false;
        }

        return true;
    }

    /**
     * 验证 ZIP 文件
     *
     * @param string $filePath 文件路径
     * @param string $header 文件头部
     * @return bool 是否验证通过
     */
    private static function verifyZipFile($filePath, $header)
    {
        // ZIP 文件的魔数是 PK
        if (substr($header, 0, 2) !== 'PK') {
            echo "  验证失败: 不是有效的 ZIP 文件\n";
            return false;
        }

        return true;
    }

    /**
     * 验证 PHAR 文件
     *
     * @param string $filePath 文件路径
     * @param string $header 文件头部
     * @return bool 是否验证通过
     */
    private static function verifyPharFile($filePath, $header)
    {
        // PHAR 文件通常以 <?php 开头或者是二进制格式
        if (strpos($header, '<?php') === 0 || strpos($header, '#!/usr/bin/env php') === 0) {
            return true;
        }

        // 检查是否为二进制 PHAR
        if (substr($header, 0, 4) === "\x89PNG" || substr($header, 0, 2) === 'PK') {
            return true;
        }

        echo "  验证失败: 不是有效的 PHAR 文件\n";
        return false;
    }

    /**
     * 验证通用文件
     *
     * @param string $filePath 文件路径
     * @param string $header 文件头部
     * @return bool 是否验证通过
     */
    private static function verifyGenericFile($filePath, $header)
    {
        // 检查是否全为空字符
        if (trim($header) === '') {
            echo "  验证失败: 文件内容为空\n";
            return false;
        }

        // 检查是否包含错误信息
        $errorPatterns = [
            'not found',
            '404',
            'error',
            'forbidden',
            'access denied',
            'file not found'
        ];

        $lowerHeader = strtolower($header);
        foreach ($errorPatterns as $pattern) {
            if (strpos($lowerHeader, $pattern) !== false) {
                echo "  验证失败: 文件包含错误信息\n";
                return false;
            }
        }

        return true;
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
