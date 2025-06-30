<?php

namespace Mirror\Log;

use Mirror\Config\ConfigManager;

/**
 * 日志管理器类
 *
 * 用于管理和记录系统日志
 * 集成PVM的日志机制
 */
class LogManager
{
    /**
     * 配置管理器
     *
     * @var ConfigManager
     */
    private $configManager;

    /**
     * 日志配置
     *
     * @var array
     */
    private $logConfig;

    /**
     * 日志目录
     *
     * @var string
     */
    private $logDir;

    /**
     * 日志文件路径
     *
     * @var array
     */
    private $logFiles = [];

    /**
     * 日志级别
     *
     * @var array
     */
    private $logLevels = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
        'critical' => 4,
    ];

    /**
     * 当前日志文件路径（PVM风格）
     *
     * @var string
     */
    private static $currentLogFile = null;

    /**
     * 命令开始时间
     *
     * @var string
     */
    private static $commandStartTime = null;

    /**
     * 是否启用文件日志
     *
     * @var bool
     */
    private static $enabled = true;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->configManager = new ConfigManager();
        $this->logConfig = $this->configManager->getLogConfig();
        $this->initLogDir();
        $this->initLogFiles();
    }

    /**
     * 初始化日志目录
     */
    private function initLogDir()
    {
        $this->logDir = $this->configManager->getLogDir();
        
        // 确保日志目录存在
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }

    /**
     * 初始化日志文件
     */
    private function initLogFiles()
    {
        // 设置各类型日志文件路径
        $this->logFiles = [
            'system' => $this->logDir . '/system.log',
            'access' => $this->logDir . '/access.log',
            'error' => $this->logDir . '/error.log',
            'sync' => $this->logDir . '/sync.log',
            'download' => $this->logDir . '/download.log',
        ];
    }

    /**
     * 记录系统日志
     *
     * @param string $message 日志消息
     * @param string $level 日志级别
     * @param array $context 上下文数据
     * @return bool 是否成功
     */
    public function log($message, $level = 'info', array $context = [])
    {
        // 检查日志级别
        if (!$this->shouldLog($level)) {
            return true;
        }

        // 格式化日志消息
        $logEntry = $this->formatLogEntry($message, $level, $context);

        // 写入系统日志文件
        return $this->writeLog('system', $logEntry);
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
    public function logAccess($ip, $method, $uri, $status = 200, $userAgent = '', $referer = '')
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

        // 写入访问日志文件
        return $this->writeLog('access', $logEntry);
    }

    /**
     * 记录错误日志
     *
     * @param string $message 错误消息
     * @param string $level 错误级别
     * @param array $context 上下文数据
     * @return bool 是否成功
     */
    public function logError($message, $level = 'error', array $context = [])
    {
        // 格式化日志消息
        $logEntry = $this->formatLogEntry($message, $level, $context);

        // 写入错误日志文件
        return $this->writeLog('error', $logEntry);
    }

    /**
     * 记录同步日志
     *
     * @param string $message 日志消息
     * @param string $level 日志级别
     * @param array $context 上下文数据
     * @return bool 是否成功
     */
    public function logSync($message, $level = 'info', array $context = [])
    {
        // 检查日志级别
        if (!$this->shouldLog($level)) {
            return true;
        }

        // 格式化日志消息
        $logEntry = $this->formatLogEntry($message, $level, $context);

        // 写入同步日志文件
        return $this->writeLog('sync', $logEntry);
    }

    /**
     * 记录下载日志
     *
     * @param string $ip 客户端IP
     * @param string $filePath 文件路径
     * @param int $fileSize 文件大小
     * @param int $duration 下载持续时间（秒）
     * @param bool $success 是否成功
     * @return bool 是否成功
     */
    public function logDownload($ip, $filePath, $fileSize, $duration, $success = true)
    {
        // 获取当前时间
        $time = date('Y-m-d H:i:s');

        // 格式化文件大小
        $formattedSize = $this->formatSize($fileSize);

        // 格式化日志条目
        $logEntry = sprintf(
            '[%s] %s "%s" %s %d秒 %s',
            $time,
            $ip,
            $filePath,
            $formattedSize,
            $duration,
            $success ? '成功' : '失败'
        );

        // 写入下载日志文件
        return $this->writeLog('download', $logEntry);
    }

    /**
     * 写入日志
     *
     * @param string $type 日志类型
     * @param string $logEntry 日志条目
     * @return bool 是否成功
     */
    private function writeLog($type, $logEntry)
    {
        // 如果日志类型不存在，则返回失败
        if (!isset($this->logFiles[$type])) {
            return false;
        }

        // 获取日志文件路径
        $logFile = $this->logFiles[$type];

        // 写入日志文件
        return file_put_contents($logFile, $logEntry . PHP_EOL, FILE_APPEND) !== false;
    }

    /**
     * 格式化日志条目
     *
     * @param string $message 日志消息
     * @param string $level 日志级别
     * @param array $context 上下文数据
     * @return string 格式化后的日志条目
     */
    private function formatLogEntry($message, $level, array $context = [])
    {
        // 获取当前时间
        $time = date('Y-m-d H:i:s');

        // 替换上下文变量
        $message = $this->interpolate($message, $context);

        // 格式化日志条目
        return sprintf('[%s] [%s] %s', $time, strtoupper($level), $message);
    }

    /**
     * 替换上下文变量
     *
     * @param string $message 日志消息
     * @param array $context 上下文数据
     * @return string 替换后的消息
     */
    private function interpolate($message, array $context = [])
    {
        // 构建替换数组
        $replace = [];
        foreach ($context as $key => $val) {
            // 检查值是否可以转换为字符串
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        // 替换上下文变量
        return strtr($message, $replace);
    }

    /**
     * 检查是否应该记录日志
     *
     * @param string $level 日志级别
     * @return bool 是否应该记录
     */
    private function shouldLog($level)
    {
        // 获取配置的日志级别
        $configLevel = $this->logConfig['log_level'] ?? 'info';

        // 如果日志级别不存在，则使用info级别
        if (!isset($this->logLevels[$level])) {
            $level = 'info';
        }

        // 如果配置的日志级别不存在，则使用info级别
        if (!isset($this->logLevels[$configLevel])) {
            $configLevel = 'info';
        }

        // 检查日志级别是否应该记录
        return $this->logLevels[$level] >= $this->logLevels[$configLevel];
    }

    /**
     * 获取日志文件内容
     *
     * @param string $type 日志类型
     * @param int $lines 行数
     * @return array 日志条目
     */
    public function getLogContent($type, $lines = 100)
    {
        // 如果日志类型不存在，则返回空数组
        if (!isset($this->logFiles[$type])) {
            return [];
        }

        // 获取日志文件路径
        $logFile = $this->logFiles[$type];

        // 如果日志文件不存在，则返回空数组
        if (!file_exists($logFile)) {
            return [];
        }

        // 读取日志文件的最后几行
        $logs = [];
        $file = new \SplFileObject($logFile, 'r');
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
     * @param string $type 日志类型
     * @return bool 是否成功
     */
    public function clearLog($type)
    {
        // 如果日志类型不存在，则返回失败
        if (!isset($this->logFiles[$type])) {
            return false;
        }

        // 获取日志文件路径
        $logFile = $this->logFiles[$type];

        // 如果日志文件不存在，则返回成功
        if (!file_exists($logFile)) {
            return true;
        }

        // 清空日志文件
        return file_put_contents($logFile, '') !== false;
    }

    /**
     * 获取日志文件路径
     *
     * @param string $type 日志类型
     * @return string 日志文件路径
     */
    public function getLogFile($type)
    {
        return $this->logFiles[$type] ?? '';
    }

    /**
     * 格式化文件大小
     *
     * @param int $size 文件大小（字节）
     * @return string 格式化后的大小
     */
    private function formatSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }

    // ========== PVM风格的日志方法 ==========

    /**
     * 初始化PVM风格的文件日志系统
     *
     * @param string $command 当前执行的命令
     * @param array $args 命令参数
     */
    public static function initPvmLogging($command = '', $args = [])
    {
        // 记录命令开始时间
        self::$commandStartTime = date('Y-m-d H:i:s');

        // 创建日志文件路径
        self::$currentLogFile = self::createPvmLogFilePath();

        // 确保日志目录存在
        self::ensurePvmLogDirectoryExists();

        // 记录命令开始
        self::logCommandStart($command, $args);
    }

    /**
     * 获取PVM风格的日志根目录
     *
     * @return string
     */
    private static function getPvmLogRootDir()
    {
        // 检测是否在开发模式（项目目录中运行）
        $projectRoot = dirname(dirname(dirname(__DIR__)));
        $isDevMode = self::isDevMode($projectRoot);

        // 开发模式：优先使用项目的 logs 目录
        if ($isDevMode) {
            $projectLogDir = $projectRoot . '/logs';
            // 确保项目logs目录存在
            if (!is_dir($projectLogDir)) {
                mkdir($projectLogDir, 0755, true);
            }
            return $projectLogDir;
        }

        // 生产模式：使用 PVM 目录下的 log 文件夹
        $homeDir = getenv('HOME');
        $pvmLogDir = $homeDir . '/.pvm/log';

        // 如果 PVM 目录存在，使用它
        if (is_dir($homeDir . '/.pvm')) {
            return $pvmLogDir;
        }

        // 最后备选：使用项目根目录下的 log 文件夹（向后兼容）
        return $projectRoot . '/log';
    }

    /**
     * 检测是否在开发模式
     *
     * @param string $projectRoot 项目根目录
     * @return bool
     */
    private static function isDevMode($projectRoot)
    {
        // 检查当前工作目录是否在项目目录内
        $currentDir = getcwd();
        $isInProjectDir = strpos($currentDir, $projectRoot) === 0;

        // 检查是否有项目文件
        $hasProjectFiles = file_exists($projectRoot . '/composer.json') &&
                          file_exists($projectRoot . '/bin/pvm-mirror') &&
                          is_dir($projectRoot . '/srcMirror');

        // 检查是否有开发环境标识
        $hasDevIndicators = is_dir($projectRoot . '/docker') ||
                           is_dir($projectRoot . '/tests') ||
                           file_exists($projectRoot . '/docker-compose.yml');

        return $isInProjectDir && $hasProjectFiles && $hasDevIndicators;
    }

    /**
     * 创建PVM风格的日志文件路径
     *
     * @return string
     */
    private static function createPvmLogFilePath()
    {
        $logDir = self::getPvmLogRootDir();
        $now = new \DateTime();

        // 格式：年/月/日/时-分-秒.log
        $year = $now->format('Y');
        $month = $now->format('m');
        $day = $now->format('d');
        $time = $now->format('H-i-s');

        return $logDir . "/{$year}/{$month}/{$day}/{$time}.log";
    }

    /**
     * 确保PVM风格的日志目录存在
     */
    private static function ensurePvmLogDirectoryExists()
    {
        if (self::$currentLogFile) {
            $logFileDir = dirname(self::$currentLogFile);
            if (!is_dir($logFileDir)) {
                mkdir($logFileDir, 0755, true);
            }
        }
    }

    /**
     * 记录命令开始
     *
     * @param string $command 命令名称
     * @param array $args 命令参数
     */
    private static function logCommandStart($command, $args)
    {
        if (!self::$enabled || !self::$currentLogFile) {
            return;
        }

        $argsStr = implode(' ', $args);
        $pid = getmypid();
        $user = get_current_user();
        $workDir = getcwd();

        $logEntry = "=== 命令开始 ===" . PHP_EOL;
        $logEntry .= "命令: $command" . PHP_EOL;
        $logEntry .= "参数: $argsStr" . PHP_EOL;
        $logEntry .= "开始时间: " . self::$commandStartTime . PHP_EOL;
        $logEntry .= "PID: $pid" . PHP_EOL;
        $logEntry .= "用户: $user" . PHP_EOL;
        $logEntry .= "工作目录: $workDir" . PHP_EOL;
        $logEntry .= PHP_EOL;

        file_put_contents(self::$currentLogFile, $logEntry, FILE_APPEND);
    }

    /**
     * 记录命令结束
     *
     * @param int $exitCode 退出代码
     */
    public static function logCommandEnd($exitCode = 0)
    {
        if (!self::$enabled || !self::$currentLogFile || !self::$commandStartTime) {
            return;
        }

        $endTime = date('Y-m-d H:i:s');
        $startTimestamp = strtotime(self::$commandStartTime);
        $endTimestamp = strtotime($endTime);
        $duration = $endTimestamp - $startTimestamp;

        $status = $exitCode === 0 ? '成功' : '失败';

        $logEntry = "=== 命令结束 ===" . PHP_EOL;
        $logEntry .= "结束时间: $endTime" . PHP_EOL;
        $logEntry .= "执行时长: {$duration}秒" . PHP_EOL;
        $logEntry .= "退出代码: $exitCode" . PHP_EOL;
        $logEntry .= "状态: $status" . PHP_EOL;
        $logEntry .= PHP_EOL;

        file_put_contents(self::$currentLogFile, $logEntry, FILE_APPEND);
    }

    /**
     * 启用或禁用文件日志
     *
     * @param bool $enabled 是否启用文件日志
     */
    public static function setEnabled($enabled)
    {
        self::$enabled = $enabled;
    }

    /**
     * 检查文件日志是否启用
     *
     * @return bool
     */
    public static function isEnabled()
    {
        return self::$enabled;
    }

    /**
     * 获取当前PVM风格的日志文件路径
     *
     * @return string
     */
    public static function getCurrentPvmLogFile()
    {
        return self::$currentLogFile;
    }

    /**
     * PVM风格的信息日志
     *
     * @param string $message 日志消息
     * @param string $prefix 前缀标识
     */
    public static function pvmInfo($message, $prefix = 'INFO')
    {
        self::writePvmLog($message, 'INFO', $prefix);
    }

    /**
     * PVM风格的调试日志
     *
     * @param string $message 日志消息
     * @param string $prefix 前缀标识
     */
    public static function pvmDebug($message, $prefix = 'DEBUG')
    {
        self::writePvmLog($message, 'DEBUG', $prefix);
    }

    /**
     * PVM风格的警告日志
     *
     * @param string $message 日志消息
     */
    public static function pvmWarning($message)
    {
        self::writePvmLog($message, 'WARNING');
    }

    /**
     * PVM风格的错误日志
     *
     * @param string $message 日志消息
     */
    public static function pvmError($message)
    {
        self::writePvmLog($message, 'ERROR');
    }

    /**
     * PVM风格的成功日志
     *
     * @param string $message 日志消息
     */
    public static function pvmSuccess($message)
    {
        self::writePvmLog($message, 'SUCCESS');
    }

    /**
     * 写入PVM风格的日志
     *
     * @param string $message 日志消息
     * @param string $level 日志级别
     * @param string $prefix 前缀标识
     */
    private static function writePvmLog($message, $level, $prefix = null)
    {
        if (!self::$enabled || !self::$currentLogFile) {
            return;
        }

        $time = date('Y-m-d H:i:s');
        $prefixStr = $prefix ? "[$prefix] " : '';
        $logEntry = "[$time] [$level] $prefixStr$message" . PHP_EOL;

        file_put_contents(self::$currentLogFile, $logEntry, FILE_APPEND);
    }
}
