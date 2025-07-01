<?php

namespace App\Helpers;

class FormatHelper
{
    /**
     * 格式化字节大小
     *
     * @param int $bytes 字节数
     * @param int $precision 精度
     * @return string 格式化后的大小字符串
     */
    public static function formatBytes($bytes, $precision = 2)
    {
        if ($bytes == 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * 格式化数字
     *
     * @param int $number 数字
     * @return string 格式化后的数字字符串
     */
    public static function formatNumber($number)
    {
        return number_format($number);
    }

    /**
     * 格式化时间
     *
     * @param string $datetime 时间字符串
     * @return string 格式化后的时间字符串
     */
    public static function formatTime($datetime)
    {
        if (!$datetime) {
            return '未知';
        }

        $time = strtotime($datetime);
        if (!$time) {
            return $datetime;
        }

        return date('Y-m-d H:i:s', $time);
    }

    /**
     * 格式化相对时间
     *
     * @param string $datetime 时间字符串
     * @return string 相对时间字符串
     */
    public static function formatRelativeTime($datetime)
    {
        if (!$datetime) {
            return '未知';
        }

        $time = strtotime($datetime);
        if (!$time) {
            return $datetime;
        }

        $diff = time() - $time;

        if ($diff < 60) {
            return '刚刚';
        } elseif ($diff < 3600) {
            return floor($diff / 60) . ' 分钟前';
        } elseif ($diff < 86400) {
            return floor($diff / 3600) . ' 小时前';
        } elseif ($diff < 2592000) {
            return floor($diff / 86400) . ' 天前';
        } else {
            return date('Y-m-d', $time);
        }
    }
}
