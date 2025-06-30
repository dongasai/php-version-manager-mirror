<?php

namespace Mirror\Monitor;

use Mirror\Config\ConfigManager;
use Mirror\Resource\ResourceManager;
use Mirror\Mirror\MirrorStatus;

/**
 * 监控管理器类
 * 
 * 用于监控系统状态和性能
 */
class MonitorManager
{
    /**
     * 配置管理器
     *
     * @var ConfigManager
     */
    private $configManager;

    /**
     * 资源管理器
     *
     * @var ResourceManager
     */
    private $resourceManager;

    /**
     * 镜像状态
     *
     * @var MirrorStatus
     */
    private $mirrorStatus;

    /**
     * 监控数据文件路径
     *
     * @var string
     */
    private $monitorFile;

    /**
     * 监控数据
     *
     * @var array
     */
    private $monitorData = [];

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->configManager = new ConfigManager();
        $this->resourceManager = new ResourceManager();
        $this->mirrorStatus = new MirrorStatus();
        $this->monitorFile = $this->configManager->getDataDir() . '/monitor.json';
        $this->loadMonitorData();
    }

    /**
     * 加载监控数据
     */
    private function loadMonitorData()
    {
        // 如果监控数据文件存在，则加载
        if (file_exists($this->monitorFile)) {
            $data = file_get_contents($this->monitorFile);
            if ($data !== false) {
                $monitorData = json_decode($data, true);
                if (is_array($monitorData)) {
                    $this->monitorData = $monitorData;
                }
            }
        }
    }

    /**
     * 保存监控数据
     */
    private function saveMonitorData()
    {
        // 保存监控数据到文件
        file_put_contents($this->monitorFile, json_encode($this->monitorData), LOCK_EX);
    }

    /**
     * 收集监控数据
     *
     * @return array 监控数据
     */
    public function collectData()
    {
        // 获取系统资源使用情况
        $resources = $this->resourceManager->checkSystemResources();

        // 获取镜像状态
        $status = $this->mirrorStatus->getStatus();

        // 获取当前时间
        $time = time();

        // 构建监控数据
        $data = [
            'timestamp' => $time,
            'date' => date('Y-m-d H:i:s', $time),
            'resources' => $resources,
            'mirror' => [
                'total_size' => $status['total_size'] ?? 0,
                'total_files' => $status['total_files'] ?? 0,
                'last_update' => $status['last_update'] ?? 0,
                'php_versions' => $status['php_versions'] ?? 0,
                'pecl_extensions' => $status['pecl_extensions'] ?? 0,
                'extensions' => $status['extensions'] ?? 0,
                'composer_packages' => $status['composer_packages'] ?? 0,
            ],
        ];

        // 添加到监控数据历史
        $this->addDataPoint($data);

        return $data;
    }

    /**
     * 添加数据点
     *
     * @param array $data 数据点
     */
    private function addDataPoint($data)
    {
        // 如果历史数据不存在，则初始化
        if (!isset($this->monitorData['history'])) {
            $this->monitorData['history'] = [];
        }

        // 添加数据点
        $this->monitorData['history'][] = $data;

        // 限制历史数据点数量
        $maxPoints = 1440; // 保存24小时的数据（每分钟一个点）
        if (count($this->monitorData['history']) > $maxPoints) {
            $this->monitorData['history'] = array_slice($this->monitorData['history'], -$maxPoints);
        }

        // 更新最新数据
        $this->monitorData['latest'] = $data;

        // 保存监控数据
        $this->saveMonitorData();
    }

    /**
     * 获取最新监控数据
     *
     * @return array 最新监控数据
     */
    public function getLatestData()
    {
        // 如果最新数据不存在，则收集数据
        if (!isset($this->monitorData['latest'])) {
            return $this->collectData();
        }

        return $this->monitorData['latest'];
    }

    /**
     * 获取历史监控数据
     *
     * @param int $hours 小时数
     * @return array 历史监控数据
     */
    public function getHistoryData($hours = 24)
    {
        // 如果历史数据不存在，则返回空数组
        if (!isset($this->monitorData['history'])) {
            return [];
        }

        // 计算时间范围
        $startTime = time() - ($hours * 3600);

        // 过滤历史数据
        $history = [];
        foreach ($this->monitorData['history'] as $data) {
            if ($data['timestamp'] >= $startTime) {
                $history[] = $data;
            }
        }

        return $history;
    }

    /**
     * 获取统计数据
     *
     * @return array 统计数据
     */
    public function getStats()
    {
        // 获取最新数据
        $latest = $this->getLatestData();

        // 获取24小时历史数据
        $history = $this->getHistoryData(24);

        // 如果没有历史数据，则返回最新数据
        if (empty($history)) {
            return [
                'latest' => $latest,
                'avg_cpu' => $latest['resources']['cpu_usage'] ?? 0,
                'avg_memory' => $latest['resources']['memory_usage'] ?? 0,
                'avg_disk' => $latest['resources']['disk_usage'] ?? 0,
                'max_cpu' => $latest['resources']['cpu_usage'] ?? 0,
                'max_memory' => $latest['resources']['memory_usage'] ?? 0,
                'max_disk' => $latest['resources']['disk_usage'] ?? 0,
                'min_cpu' => $latest['resources']['cpu_usage'] ?? 0,
                'min_memory' => $latest['resources']['memory_usage'] ?? 0,
                'min_disk' => $latest['resources']['disk_usage'] ?? 0,
            ];
        }

        // 计算统计数据
        $cpuValues = array_column(array_column($history, 'resources'), 'cpu_usage');
        $memoryValues = array_column(array_column($history, 'resources'), 'memory_usage');
        $diskValues = array_column(array_column($history, 'resources'), 'disk_usage');

        // 过滤掉无效值
        $cpuValues = array_filter($cpuValues, function($value) {
            return is_numeric($value);
        });
        $memoryValues = array_filter($memoryValues, function($value) {
            return is_numeric($value);
        });
        $diskValues = array_filter($diskValues, function($value) {
            return is_numeric($value);
        });

        // 计算平均值、最大值和最小值
        $stats = [
            'latest' => $latest,
            'avg_cpu' => !empty($cpuValues) ? array_sum($cpuValues) / count($cpuValues) : 0,
            'avg_memory' => !empty($memoryValues) ? array_sum($memoryValues) / count($memoryValues) : 0,
            'avg_disk' => !empty($diskValues) ? array_sum($diskValues) / count($diskValues) : 0,
            'max_cpu' => !empty($cpuValues) ? max($cpuValues) : 0,
            'max_memory' => !empty($memoryValues) ? max($memoryValues) : 0,
            'max_disk' => !empty($diskValues) ? max($diskValues) : 0,
            'min_cpu' => !empty($cpuValues) ? min($cpuValues) : 0,
            'min_memory' => !empty($memoryValues) ? min($memoryValues) : 0,
            'min_disk' => !empty($diskValues) ? min($diskValues) : 0,
        ];

        return $stats;
    }

    /**
     * 清空监控数据
     *
     * @return bool 是否成功
     */
    public function clearData()
    {
        // 清空监控数据
        $this->monitorData = [];

        // 保存监控数据
        $this->saveMonitorData();

        return true;
    }

    /**
     * 检查系统健康状态
     *
     * @return array 健康状态
     */
    public function checkHealth()
    {
        // 获取最新数据
        $latest = $this->getLatestData();

        // 获取资源配置
        $resourceConfig = $this->configManager->getResourceConfig();

        // 检查CPU使用率
        $cpuStatus = 'normal';
        $cpuUsage = $latest['resources']['cpu_usage'] ?? 0;
        $cpuThreshold = $resourceConfig['high_load_threshold'] ?? 80;
        if ($cpuUsage >= $cpuThreshold) {
            $cpuStatus = 'high';
        }

        // 检查内存使用率
        $memoryStatus = 'normal';
        $memoryUsage = $latest['resources']['memory_usage'] ?? 0;
        $memoryThreshold = $resourceConfig['high_memory_threshold'] ?? 80;
        if ($memoryUsage >= $memoryThreshold) {
            $memoryStatus = 'high';
        }

        // 检查磁盘使用率
        $diskStatus = 'normal';
        $diskUsage = $latest['resources']['disk_usage'] ?? 0;
        $diskThreshold = $resourceConfig['high_disk_threshold'] ?? 90;
        if ($diskUsage >= $diskThreshold) {
            $diskStatus = 'high';
        }

        // 检查镜像状态
        $mirrorStatus = 'normal';
        $lastUpdate = $latest['mirror']['last_update'] ?? 0;
        $now = time();
        $updateThreshold = 86400; // 24小时
        if ($lastUpdate < $now - $updateThreshold) {
            $mirrorStatus = 'outdated';
        }

        // 综合健康状态
        $overallStatus = 'healthy';
        if ($cpuStatus === 'high' || $memoryStatus === 'high' || $diskStatus === 'high') {
            $overallStatus = 'warning';
        }
        if ($mirrorStatus === 'outdated') {
            $overallStatus = 'warning';
        }

        return [
            'overall' => $overallStatus,
            'cpu' => $cpuStatus,
            'memory' => $memoryStatus,
            'disk' => $diskStatus,
            'mirror' => $mirrorStatus,
            'timestamp' => time(),
            'date' => date('Y-m-d H:i:s'),
        ];
    }
}
