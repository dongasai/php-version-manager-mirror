<?php

use App\Helpers\FormatHelper;

if (!function_exists('formatBytes')) {
    /**
     * 格式化字节大小
     *
     * @param int $bytes 字节数
     * @param int $precision 精度
     * @return string 格式化后的大小字符串
     */
    function formatBytes($bytes, $precision = 2)
    {
        return FormatHelper::formatBytes($bytes, $precision);
    }
}

if (!function_exists('formatNumber')) {
    /**
     * 格式化数字
     *
     * @param int $number 数字
     * @return string 格式化后的数字字符串
     */
    function formatNumber($number)
    {
        return FormatHelper::formatNumber($number);
    }
}

if (!function_exists('formatTime')) {
    /**
     * 格式化时间
     *
     * @param string $datetime 时间字符串
     * @return string 格式化后的时间字符串
     */
    function formatTime($datetime)
    {
        return FormatHelper::formatTime($datetime);
    }
}

if (!function_exists('formatRelativeTime')) {
    /**
     * 格式化相对时间
     *
     * @param string $datetime 时间字符串
     * @return string 相对时间字符串
     */
    function formatRelativeTime($datetime)
    {
        return FormatHelper::formatRelativeTime($datetime);
    }
}
