<?php

namespace Mirror\Cache;

use Mirror\Config\ConfigManager;

/**
 * 缓存管理器类
 * 
 * 用于管理镜像站的缓存
 */
class CacheManager
{
    /**
     * 配置管理器
     *
     * @var ConfigManager
     */
    private $configManager;

    /**
     * 缓存配置
     *
     * @var array
     */
    private $cacheConfig;

    /**
     * 缓存目录
     *
     * @var string
     */
    private $cacheDir;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->configManager = new ConfigManager();
        $this->cacheConfig = $this->configManager->getCacheConfig();
        $this->initCacheDir();
    }

    /**
     * 初始化缓存目录
     */
    private function initCacheDir()
    {
        $this->cacheDir = $this->configManager->getCacheDir();
        
        // 确保缓存目录存在
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * 获取缓存项
     *
     * @param string $key 缓存键
     * @param mixed $default 默认值
     * @return mixed 缓存值或默认值
     */
    public function get($key, $default = null)
    {
        // 如果缓存未启用，则返回默认值
        if (!$this->isEnabled()) {
            return $default;
        }

        // 获取缓存文件路径
        $cacheFile = $this->getCacheFilePath($key);

        // 如果缓存文件不存在，则返回默认值
        if (!file_exists($cacheFile)) {
            return $default;
        }

        // 读取缓存文件
        $data = file_get_contents($cacheFile);
        if ($data === false) {
            return $default;
        }

        // 解码缓存数据
        $cacheData = json_decode($data, true);
        if (!is_array($cacheData) || !isset($cacheData['value']) || !isset($cacheData['expires'])) {
            return $default;
        }

        // 检查缓存是否过期
        if ($cacheData['expires'] > 0 && $cacheData['expires'] < time()) {
            // 缓存已过期，删除缓存文件
            @unlink($cacheFile);
            return $default;
        }

        return $cacheData['value'];
    }

    /**
     * 设置缓存项
     *
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int $ttl 过期时间（秒）
     * @return bool 是否成功
     */
    public function set($key, $value, $ttl = 0)
    {
        // 如果缓存未启用，则返回失败
        if (!$this->isEnabled()) {
            return false;
        }

        // 获取缓存文件路径
        $cacheFile = $this->getCacheFilePath($key);

        // 计算过期时间
        $expires = $ttl > 0 ? time() + $ttl : 0;

        // 编码缓存数据
        $cacheData = [
            'value' => $value,
            'expires' => $expires,
            'created' => time()
        ];

        // 写入缓存文件
        $result = file_put_contents($cacheFile, json_encode($cacheData), LOCK_EX);

        return $result !== false;
    }

    /**
     * 删除缓存项
     *
     * @param string $key 缓存键
     * @return bool 是否成功
     */
    public function delete($key)
    {
        // 获取缓存文件路径
        $cacheFile = $this->getCacheFilePath($key);

        // 如果缓存文件不存在，则返回成功
        if (!file_exists($cacheFile)) {
            return true;
        }

        // 删除缓存文件
        return @unlink($cacheFile);
    }

    /**
     * 清空所有缓存
     *
     * @return bool 是否成功
     */
    public function clear()
    {
        // 获取所有缓存文件
        $files = glob($this->cacheDir . '/*.cache');
        if ($files === false) {
            return false;
        }

        // 删除所有缓存文件
        $success = true;
        foreach ($files as $file) {
            if (!@unlink($file)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * 检查缓存项是否存在
     *
     * @param string $key 缓存键
     * @return bool 是否存在
     */
    public function has($key)
    {
        // 如果缓存未启用，则返回false
        if (!$this->isEnabled()) {
            return false;
        }

        // 获取缓存文件路径
        $cacheFile = $this->getCacheFilePath($key);

        // 如果缓存文件不存在，则返回false
        if (!file_exists($cacheFile)) {
            return false;
        }

        // 读取缓存文件
        $data = file_get_contents($cacheFile);
        if ($data === false) {
            return false;
        }

        // 解码缓存数据
        $cacheData = json_decode($data, true);
        if (!is_array($cacheData) || !isset($cacheData['value']) || !isset($cacheData['expires'])) {
            return false;
        }

        // 检查缓存是否过期
        if ($cacheData['expires'] > 0 && $cacheData['expires'] < time()) {
            // 缓存已过期，删除缓存文件
            @unlink($cacheFile);
            return false;
        }

        return true;
    }

    /**
     * 获取缓存文件路径
     *
     * @param string $key 缓存键
     * @return string 缓存文件路径
     */
    private function getCacheFilePath($key)
    {
        // 对缓存键进行哈希处理，避免文件名过长或包含非法字符
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }

    /**
     * 检查缓存是否启用
     *
     * @return bool 是否启用
     */
    public function isEnabled()
    {
        return isset($this->cacheConfig['enable_cache']) && $this->cacheConfig['enable_cache'];
    }

    /**
     * 获取缓存统计信息
     *
     * @return array 统计信息
     */
    public function getStats()
    {
        // 获取所有缓存文件
        $files = glob($this->cacheDir . '/*.cache');
        if ($files === false) {
            $files = [];
        }

        // 统计信息
        $stats = [
            'total' => count($files),
            'size' => 0,
            'expired' => 0,
            'valid' => 0
        ];

        // 遍历所有缓存文件
        foreach ($files as $file) {
            // 获取文件大小
            $size = filesize($file);
            if ($size !== false) {
                $stats['size'] += $size;
            }

            // 读取缓存文件
            $data = file_get_contents($file);
            if ($data === false) {
                continue;
            }

            // 解码缓存数据
            $cacheData = json_decode($data, true);
            if (!is_array($cacheData) || !isset($cacheData['value']) || !isset($cacheData['expires'])) {
                continue;
            }

            // 检查缓存是否过期
            if ($cacheData['expires'] > 0 && $cacheData['expires'] < time()) {
                $stats['expired']++;
            } else {
                $stats['valid']++;
            }
        }

        return $stats;
    }

    /**
     * 清理过期缓存
     *
     * @return int 清理的缓存数量
     */
    public function cleanExpired()
    {
        // 获取所有缓存文件
        $files = glob($this->cacheDir . '/*.cache');
        if ($files === false) {
            return 0;
        }

        // 清理计数
        $count = 0;

        // 遍历所有缓存文件
        foreach ($files as $file) {
            // 读取缓存文件
            $data = file_get_contents($file);
            if ($data === false) {
                continue;
            }

            // 解码缓存数据
            $cacheData = json_decode($data, true);
            if (!is_array($cacheData) || !isset($cacheData['value']) || !isset($cacheData['expires'])) {
                continue;
            }

            // 检查缓存是否过期
            if ($cacheData['expires'] > 0 && $cacheData['expires'] < time()) {
                // 缓存已过期，删除缓存文件
                if (@unlink($file)) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
