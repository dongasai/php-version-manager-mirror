<?php

namespace App\Utils;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 文件下载工具类
 */
class FileDownloader
{
    /**
     * 下载文件
     *
     * @param string $url 文件URL
     * @param string $destination 目标路径
     * @param array $options 下载选项
     * @return bool 是否下载成功
     */
    public static function downloadFile(string $url, string $destination, array $options = []): bool
    {
        // 默认选项
        $defaultOptions = [
            'min_size' => 1024,        // 最小文件大小 (1KB)
            'max_retries' => 3,        // 最大重试次数
            'timeout' => 300,          // 超时时间 (秒)
            'verify_content' => true,  // 是否验证内容
            'expected_type' => null,   // 期望的文件类型
            'user_agent' => 'PVM-Mirror/1.0',
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

            try {
                // 尝试下载
                if (self::attemptDownload($url, $destination, $options)) {
                    // 验证下载的文件
                    if (self::validateDownloadedFile($destination, $options)) {
                        return true;
                    } else {
                        Log::warning("文件验证失败，重试中", [
                            'url' => $url,
                            'attempt' => $retries,
                            'max_retries' => $options['max_retries']
                        ]);
                        if (file_exists($destination)) {
                            unlink($destination);
                        }
                    }
                } else {
                    Log::warning("下载失败，重试中", [
                        'url' => $url,
                        'attempt' => $retries,
                        'max_retries' => $options['max_retries']
                    ]);
                }
            } catch (\Exception $e) {
                Log::error("下载异常", [
                    'url' => $url,
                    'error' => $e->getMessage(),
                    'attempt' => $retries
                ]);
            }

            // 如果不是最后一次重试，等待一段时间
            if ($retries < $options['max_retries']) {
                sleep(2);
            }
        }

        Log::error("下载失败: 已达到最大重试次数", ['url' => $url]);
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
    private static function attemptDownload(string $url, string $destination, array $options): bool
    {
        try {
            $response = Http::timeout($options['timeout'])
                ->withUserAgent($options['user_agent'])
                ->withOptions([
                    'verify' => false, // 跳过SSL验证
                    'sink' => $destination,
                ])
                ->get($url);

            if ($response->successful()) {
                return true;
            } else {
                Log::warning("HTTP请求失败", [
                    'url' => $url,
                    'status' => $response->status()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error("下载请求异常", [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 验证下载的文件
     *
     * @param string $filePath 文件路径
     * @param array $options 验证选项
     * @return bool 是否验证通过
     */
    private static function validateDownloadedFile(string $filePath, array $options): bool
    {
        if (!file_exists($filePath)) {
            Log::error("验证失败: 文件不存在", ['file' => $filePath]);
            return false;
        }

        // 检查文件大小
        $fileSize = filesize($filePath);
        if ($fileSize < $options['min_size']) {
            Log::error("验证失败: 文件太小", [
                'file' => $filePath,
                'size' => $fileSize,
                'min_size' => $options['min_size']
            ]);
            return false;
        }

        // 检查文件是否为空
        if ($fileSize === 0) {
            Log::error("验证失败: 文件为空", ['file' => $filePath]);
            return false;
        }

        // 验证文件内容
        if ($options['verify_content']) {
            if (!self::verifyFileContent($filePath, $options)) {
                return false;
            }
        }

        Log::info("文件验证通过", [
            'file' => $filePath,
            'size' => self::formatSize($fileSize)
        ]);
        return true;
    }

    /**
     * 验证文件内容
     *
     * @param string $filePath 文件路径
     * @param array $options 验证选项
     * @return bool 是否验证通过
     */
    private static function verifyFileContent(string $filePath, array $options): bool
    {
        $expectedType = $options['expected_type'] ?? null;
        
        if (!$expectedType) {
            return true; // 如果没有指定期望类型，跳过内容验证
        }

        switch ($expectedType) {
            case 'tar.gz':
            case 'tgz':
                return self::validateGzipFile($filePath);
            case 'phar':
                return self::validatePharFile($filePath);
            default:
                return true;
        }
    }

    /**
     * 验证Gzip文件
     *
     * @param string $filePath 文件路径
     * @return bool 是否有效
     */
    private static function validateGzipFile(string $filePath): bool
    {
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }

        // 检查Gzip魔数
        $header = fread($handle, 3);
        fclose($handle);

        // Gzip文件的魔数是 1f 8b 08
        return $header === "\x1f\x8b\x08";
    }

    /**
     * 验证Phar文件
     *
     * @param string $filePath 文件路径
     * @return bool 是否有效
     */
    private static function validatePharFile(string $filePath): bool
    {
        try {
            // 尝试打开Phar文件
            $phar = new \Phar($filePath);
            return true;
        } catch (\Exception $e) {
            Log::error("Phar文件验证失败", [
                'file' => $filePath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 格式化文件大小
     *
     * @param int $bytes 字节数
     * @return string 格式化后的大小
     */
    private static function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        
        return sprintf("%.1f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
