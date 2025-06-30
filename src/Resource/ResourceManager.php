<?php

namespace Mirror\Resource;

use Mirror\Config\ConfigManager;

/**
 * 资源管理器类
 * 
 * 用于管理和限制系统资源使用
 */
class ResourceManager
{
    /**
     * 配置管理器
     *
     * @var ConfigManager
     */
    private $configManager;

    /**
     * 资源配置
     *
     * @var array
     */
    private $resourceConfig;

    /**
     * 当前活动下载数
     *
     * @var int
     */
    private $activeDownloads = 0;

    /**
     * IP请求计数器
     *
     * @var array
     */
    private $ipRequestCounter = [];

    /**
     * 资源使用状态
     *
     * @var array
     */
    private $resourceStatus = [];

    /**
     * 锁文件路径
     *
     * @var string
     */
    private $lockFile;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->configManager = new ConfigManager();
        $this->resourceConfig = $this->configManager->getResourceConfig();
        $this->lockFile = $this->configManager->getDataDir() . '/resource.lock';
        $this->loadResourceStatus();
    }

    /**
     * 加载资源状态
     */
    private function loadResourceStatus()
    {
        // 如果锁文件存在，则加载资源状态
        if (file_exists($this->lockFile)) {
            $data = file_get_contents($this->lockFile);
            if ($data !== false) {
                $status = json_decode($data, true);
                if (is_array($status)) {
                    $this->resourceStatus = $status;
                    $this->activeDownloads = $status['active_downloads'] ?? 0;
                    $this->ipRequestCounter = $status['ip_request_counter'] ?? [];
                }
            }
        }

        // 清理过期的IP请求计数
        $this->cleanupIpRequestCounter();
    }

    /**
     * 保存资源状态
     */
    private function saveResourceStatus()
    {
        // 更新资源状态
        $this->resourceStatus = [
            'active_downloads' => $this->activeDownloads,
            'ip_request_counter' => $this->ipRequestCounter,
            'last_updated' => time(),
        ];

        // 保存到锁文件
        file_put_contents($this->lockFile, json_encode($this->resourceStatus), LOCK_EX);
    }

    /**
     * 清理过期的IP请求计数
     */
    private function cleanupIpRequestCounter()
    {
        $now = time();
        foreach ($this->ipRequestCounter as $ip => $data) {
            // 如果最后更新时间超过1小时，则清理
            if (isset($data['last_updated']) && $now - $data['last_updated'] > 3600) {
                unset($this->ipRequestCounter[$ip]);
            }
        }
    }

    /**
     * 检查是否可以开始新的下载
     *
     * @return bool 是否允许下载
     */
    public function canStartDownload()
    {
        // 如果未启用资源限制，则允许下载
        if (!$this->isEnabled()) {
            return true;
        }

        // 获取最大并发下载数
        $maxDownloads = $this->resourceConfig['max_concurrent_downloads'] ?? 10;

        // 如果当前活动下载数小于最大并发下载数，则允许下载
        return $this->activeDownloads < $maxDownloads;
    }

    /**
     * 开始下载
     *
     * @return bool 是否成功
     */
    public function startDownload()
    {
        // 如果未启用资源限制，则直接返回成功
        if (!$this->isEnabled()) {
            return true;
        }

        // 如果不能开始新的下载，则返回失败
        if (!$this->canStartDownload()) {
            return false;
        }

        // 增加活动下载数
        $this->activeDownloads++;

        // 保存资源状态
        $this->saveResourceStatus();

        return true;
    }

    /**
     * 结束下载
     *
     * @return bool 是否成功
     */
    public function endDownload()
    {
        // 如果未启用资源限制，则直接返回成功
        if (!$this->isEnabled()) {
            return true;
        }

        // 减少活动下载数
        if ($this->activeDownloads > 0) {
            $this->activeDownloads--;
        }

        // 保存资源状态
        $this->saveResourceStatus();

        return true;
    }

    /**
     * 检查IP请求频率
     *
     * @param string $ip IP地址
     * @return bool 是否允许请求
     */
    public function checkIpRequestRate($ip)
    {
        // 如果未启用资源限制，则允许请求
        if (!$this->isEnabled()) {
            return true;
        }

        // 获取最大请求频率
        $maxRequestsPerMinute = $this->resourceConfig['max_requests_per_minute'] ?? 60;

        // 如果IP不在计数器中，则初始化
        if (!isset($this->ipRequestCounter[$ip])) {
            $this->ipRequestCounter[$ip] = [
                'count' => 0,
                'last_updated' => time(),
                'window_start' => time(),
            ];
        }

        // 获取当前时间
        $now = time();

        // 如果时间窗口已经过去，则重置计数
        if ($now - $this->ipRequestCounter[$ip]['window_start'] >= 60) {
            $this->ipRequestCounter[$ip] = [
                'count' => 0,
                'last_updated' => $now,
                'window_start' => $now,
            ];
        }

        // 增加请求计数
        $this->ipRequestCounter[$ip]['count']++;
        $this->ipRequestCounter[$ip]['last_updated'] = $now;

        // 保存资源状态
        $this->saveResourceStatus();

        // 如果请求计数超过最大请求频率，则拒绝请求
        return $this->ipRequestCounter[$ip]['count'] <= $maxRequestsPerMinute;
    }

    /**
     * 获取下载速度限制
     *
     * @return int 下载速度限制（字节/秒）
     */
    public function getDownloadSpeedLimit()
    {
        // 如果未启用资源限制，则返回0（无限制）
        if (!$this->isEnabled()) {
            return 0;
        }

        // 获取下载速度限制
        $speedLimit = $this->resourceConfig['download_speed_limit'] ?? 0;

        // 如果当前活动下载数大于1，则按比例减少速度限制
        if ($this->activeDownloads > 1 && $speedLimit > 0) {
            $speedLimit = (int)($speedLimit / $this->activeDownloads);
        }

        return $speedLimit;
    }

    /**
     * 检查系统资源使用情况
     *
     * @return array 系统资源使用情况
     */
    public function checkSystemResources()
    {
        $resources = [
            'cpu_usage' => $this->getCpuUsage(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage(),
            'active_downloads' => $this->activeDownloads,
        ];

        return $resources;
    }

    /**
     * 获取CPU使用率
     *
     * @return float CPU使用率（百分比）
     */
    private function getCpuUsage()
    {
        // 在Linux系统上获取CPU使用率
        if (PHP_OS_FAMILY === 'Linux') {
            $load = sys_getloadavg();
            $cores = $this->getCpuCores();
            if ($cores > 0) {
                return min(100, round(($load[0] / $cores) * 100, 2));
            }
        }

        // 如果无法获取，则返回0
        return 0;
    }

    /**
     * 获取CPU核心数
     *
     * @return int CPU核心数
     */
    private function getCpuCores()
    {
        // 在Linux系统上获取CPU核心数
        if (PHP_OS_FAMILY === 'Linux') {
            $cmd = "nproc";
            $cores = (int)shell_exec($cmd);
            if ($cores > 0) {
                return $cores;
            }

            // 尝试从/proc/cpuinfo获取
            $cmd = "grep -c processor /proc/cpuinfo";
            $cores = (int)shell_exec($cmd);
            if ($cores > 0) {
                return $cores;
            }
        }

        // 如果无法获取，则返回默认值
        return 1;
    }

    /**
     * 获取内存使用率
     *
     * @return float 内存使用率（百分比）
     */
    private function getMemoryUsage()
    {
        // 在Linux系统上获取内存使用率
        if (PHP_OS_FAMILY === 'Linux') {
            $memInfo = file_get_contents('/proc/meminfo');
            if ($memInfo !== false) {
                preg_match('/MemTotal:\s+(\d+)/', $memInfo, $matches);
                $total = isset($matches[1]) ? (int)$matches[1] : 0;

                preg_match('/MemFree:\s+(\d+)/', $memInfo, $matches);
                $free = isset($matches[1]) ? (int)$matches[1] : 0;

                preg_match('/Buffers:\s+(\d+)/', $memInfo, $matches);
                $buffers = isset($matches[1]) ? (int)$matches[1] : 0;

                preg_match('/Cached:\s+(\d+)/', $memInfo, $matches);
                $cached = isset($matches[1]) ? (int)$matches[1] : 0;

                if ($total > 0) {
                    $used = $total - $free - $buffers - $cached;
                    return round(($used / $total) * 100, 2);
                }
            }
        }

        // 如果无法获取，则返回0
        return 0;
    }

    /**
     * 获取磁盘使用率
     *
     * @return float 磁盘使用率（百分比）
     */
    private function getDiskUsage()
    {
        // 获取数据目录所在的磁盘使用率
        $dataDir = $this->configManager->getDataDir();
        if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
            $free = disk_free_space($dataDir);
            $total = disk_total_space($dataDir);
            if ($total > 0) {
                $used = $total - $free;
                return round(($used / $total) * 100, 2);
            }
        }

        // 如果无法获取，则返回0
        return 0;
    }

    /**
     * 检查是否启用资源限制
     *
     * @return bool 是否启用
     */
    public function isEnabled()
    {
        return isset($this->resourceConfig['enable_resource_limits']) && 
               $this->resourceConfig['enable_resource_limits'];
    }

    /**
     * 获取资源状态
     *
     * @return array 资源状态
     */
    public function getResourceStatus()
    {
        $status = $this->checkSystemResources();
        $status['enabled'] = $this->isEnabled();
        $status['max_concurrent_downloads'] = $this->resourceConfig['max_concurrent_downloads'] ?? 10;
        $status['max_requests_per_minute'] = $this->resourceConfig['max_requests_per_minute'] ?? 60;
        $status['download_speed_limit'] = $this->formatSpeed($this->resourceConfig['download_speed_limit'] ?? 0);
        $status['ip_request_count'] = count($this->ipRequestCounter);

        return $status;
    }

    /**
     * 格式化速度
     *
     * @param int $speed 速度（字节/秒）
     * @return string 格式化后的速度
     */
    private function formatSpeed($speed)
    {
        if ($speed <= 0) {
            return '无限制';
        }

        $units = ['B/s', 'KB/s', 'MB/s', 'GB/s', 'TB/s'];
        $i = 0;
        while ($speed >= 1024 && $i < count($units) - 1) {
            $speed /= 1024;
            $i++;
        }
        return round($speed, 2) . ' ' . $units[$i];
    }
}
