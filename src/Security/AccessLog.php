<?php

namespace Mirror\Security;

use Mirror\Config\ConfigManager;

/**
 * 访问日志类
 * 
 * 用于记录对镜像服务的访问日志
 */
class AccessLog
{
    /**
     * 配置管理器
     *
     * @var ConfigManager
     */
    private $configManager;

    /**
     * 日志文件路径
     *
     * @var string
     */
    private $logFile;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->configManager = new ConfigManager();
        $this->initLogFile();
    }

    /**
     * 初始化日志文件
     */
    private function initLogFile()
    {
        $logDir = $this->configManager->getLogDir();
        
        // 确保日志目录存在
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        // 设置日志文件路径
        $this->logFile = $logDir . '/access.log';
    }

    /**
     * 记录访问日志
     *
     * @param string $ip 客户端IP
     * @param string $method 请求方法
     * @param string $uri 请求URI
     * @param int $status HTTP状态码
     * @param string $userAgent 用户代理
     * @param string $referer 引用页
     * @return bool 是否成功
     */
    public function log($ip, $method, $uri, $status = 200, $userAgent = '', $referer = '')
    {
        // 获取当前时间
        $time = date('Y-m-d H:i:s');

        // 如果未提供用户代理，则从服务器变量中获取
        if (empty($userAgent) && isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
        }

        // 如果未提供引用页，则从服务器变量中获取
        if (empty($referer) && isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
        }

        // 格式化日志条目
        $logEntry = sprintf(
            '[%s] %s "%s %s" %d "%s" "%s"',
            $time,
            $ip,
            $method,
            $uri,
            $status,
            $referer,
            $userAgent
        );

        // 写入日志文件
        return file_put_contents($this->logFile, $logEntry . PHP_EOL, FILE_APPEND) !== false;
    }

    /**
     * 记录访问被拒绝
     *
     * @param string $ip 客户端IP
     * @param string $method 请求方法
     * @param string $uri 请求URI
     * @param string $reason 拒绝原因
     * @return bool 是否成功
     */
    public function logDenied($ip, $method, $uri, $reason = 'IP not in whitelist')
    {
        // 获取当前时间
        $time = date('Y-m-d H:i:s');

        // 获取用户代理
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        // 获取引用页
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        // 格式化日志条目
        $logEntry = sprintf(
            '[%s] %s "%s %s" 403 "DENIED: %s" "%s" "%s"',
            $time,
            $ip,
            $method,
            $uri,
            $reason,
            $referer,
            $userAgent
        );

        // 写入日志文件
        return file_put_contents($this->logFile, $logEntry . PHP_EOL, FILE_APPEND) !== false;
    }

    /**
     * 获取最近的访问日志
     *
     * @param int $lines 行数
     * @return array 日志条目
     */
    public function getRecentLogs($lines = 100)
    {
        // 如果日志文件不存在，则返回空数组
        if (!file_exists($this->logFile)) {
            return [];
        }

        // 读取日志文件的最后几行
        $logs = [];
        $file = new \SplFileObject($this->logFile, 'r');
        $file->seek(PHP_INT_MAX); // 移动到文件末尾
        $totalLines = $file->key(); // 获取总行数

        // 计算起始行
        $startLine = max(0, $totalLines - $lines);

        // 读取指定行数的日志
        $file->seek($startLine);
        while (!$file->eof()) {
            $line = $file->fgets();
            if (!empty($line)) {
                $logs[] = $line;
            }
        }

        return $logs;
    }

    /**
     * 清空日志文件
     *
     * @return bool 是否成功
     */
    public function clearLogs()
    {
        // 如果日志文件不存在，则返回成功
        if (!file_exists($this->logFile)) {
            return true;
        }

        // 清空日志文件
        return file_put_contents($this->logFile, '') !== false;
    }

    /**
     * 获取日志文件路径
     *
     * @return string
     */
    public function getLogFile()
    {
        return $this->logFile;
    }
}
