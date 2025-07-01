<?php

namespace Mirror\Log;

/**
 * 控制台和文件日志集成器
 * 
 * 提供类似PVM的日志功能，同时支持控制台输出和文件记录
 */
class Logger
{
    /**
     * 日志级别常量
     */
    const SILENT = 0;   // 静默模式
    const NORMAL = 1;   // 普通模式
    const VERBOSE = 2;  // 详细模式
    const DEBUG = 3;    // 调试模式

    /**
     * 当前日志级别
     *
     * @var int
     */
    private static $level = self::NORMAL;

    /**
     * 是否启用文件日志
     *
     * @var bool
     */
    private static $fileLoggingEnabled = true;
    
    /**
     * 设置日志级别
     *
     * @param int $level 日志级别
     */
    public static function setLevel($level)
    {
        self::$level = $level;
    }

    /**
     * 启用或禁用文件日志
     *
     * @param bool $enabled 是否启用文件日志
     */
    public static function setFileLoggingEnabled($enabled)
    {
        self::$fileLoggingEnabled = $enabled;
        LogManager::setEnabled($enabled);
    }

    /**
     * 检查文件日志是否启用
     *
     * @return bool
     */
    public static function isFileLoggingEnabled()
    {
        return self::$fileLoggingEnabled;
    }
    
    /**
     * 获取当前日志级别
     *
     * @return int
     */
    public static function getLevel()
    {
        return self::$level;
    }
    
    /**
     * 静默模式输出（只有错误和最重要的信息）
     *
     * @param string $message 消息
     * @param string $color 颜色代码
     */
    public static function silent($message, $color = '')
    {
        if (self::$level >= self::SILENT) {
            self::output($message, $color);
        }
    }
    
    /**
     * 普通模式输出（默认级别）
     *
     * @param string $message 消息
     * @param string $color 颜色代码
     */
    public static function info($message, $color = '')
    {
        if (self::$level >= self::NORMAL) {
            self::output($message, $color);
        }

        // 写入文件日志
        if (self::$fileLoggingEnabled) {
            LogManager::pvmInfo($message);
        }
    }
    
    /**
     * 详细模式输出
     *
     * @param string $message 消息
     * @param string $color 颜色代码
     */
    public static function verbose($message, $color = '')
    {
        if (self::$level >= self::VERBOSE) {
            self::output($message, $color);
        }

        // 写入文件日志
        if (self::$fileLoggingEnabled) {
            LogManager::pvmDebug($message, 'VERBOSE');
        }
    }
    
    /**
     * 调试模式输出
     *
     * @param string $message 消息
     * @param string $color 颜色代码
     */
    public static function debug($message, $color = '')
    {
        if (self::$level >= self::DEBUG) {
            self::output($message, $color);
        }

        // 写入文件日志
        if (self::$fileLoggingEnabled) {
            LogManager::pvmDebug($message);
        }
    }

    /**
     * 成功消息（总是显示）
     *
     * @param string $message 消息
     */
    public static function success($message)
    {
        self::output($message, "\033[32m");

        // 写入文件日志
        if (self::$fileLoggingEnabled) {
            LogManager::pvmSuccess($message);
        }
    }

    /**
     * 警告消息（总是显示）
     *
     * @param string $message 消息
     */
    public static function warning($message)
    {
        self::output($message, "\033[33m");

        // 写入文件日志
        if (self::$fileLoggingEnabled) {
            LogManager::pvmWarning($message);
        }
    }

    /**
     * 错误消息（总是显示）
     *
     * @param string $message 消息
     */
    public static function error($message)
    {
        self::output($message, "\033[31m");

        // 写入文件日志
        if (self::$fileLoggingEnabled) {
            LogManager::pvmError($message);
        }
    }

    /**
     * 输出消息到控制台
     *
     * @param string $message 消息
     * @param string $color 颜色代码
     */
    private static function output($message, $color = '')
    {
        if ($color) {
            echo $color . $message . "\033[0m" . PHP_EOL;
        } else {
            echo $message . PHP_EOL;
        }
    }

    /**
     * 解析日志级别参数
     *
     * @param array $args 命令行参数
     * @return int 日志级别
     */
    public static function parseLogLevel($args)
    {
        // 检查详细模式参数
        if (in_array('-v', $args) || in_array('--verbose', $args)) {
            return self::VERBOSE;
        }

        // 检查调试模式参数
        if (in_array('-d', $args) || in_array('--debug', $args)) {
            return self::DEBUG;
        }

        // 检查静默模式参数
        if (in_array('-q', $args) || in_array('--quiet', $args)) {
            return self::SILENT;
        }

        // 默认普通模式
        return self::NORMAL;
    }

    /**
     * 从参数中移除日志级别相关的参数
     *
     * @param array $args 命令行参数
     * @return array 清理后的参数
     */
    public static function cleanLogLevelArgs($args)
    {
        $cleanArgs = [];
        foreach ($args as $arg) {
            if (!in_array($arg, ['-v', '--verbose', '-d', '--debug', '-q', '--quiet'])) {
                $cleanArgs[] = $arg;
            }
        }
        return $cleanArgs;
    }
}
