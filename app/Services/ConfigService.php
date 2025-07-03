<?php

namespace App\Services;

use App\Models\SystemConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 配置管理服务
 * 
 * 负责管理系统配置，包括镜像配置、运行时配置等
 */
class ConfigService
{
    /**
     * 缓存键前缀
     */
    const CACHE_PREFIX = 'config:';
    
    /**
     * 缓存时间（秒）
     */
    const CACHE_TTL = 3600;

    /**
     * 获取镜像配置
     *
     * @param string|null $key 配置键，为null时返回所有配置
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getMirrorConfig(string $key = null, $default = null)
    {
        $config = config('mirror');

        if ($key === null) {
            return $config;
        }

        return data_get($config, $key, $default);
    }

    /**
     * 获取PHP版本配置（按旧系统格式）
     *
     * @param string|null $majorVersion 主版本号，为null时返回所有版本
     * @return array
     */
    public function getPhpVersions(string $majorVersion = null): array
    {
        if ($majorVersion === null) {
            // 返回所有主版本的配置
            $allVersions = [];
            $majorVersions = ['5.4', '5.5', '5.6', '7.0', '7.1', '7.2', '7.3', '7.4', '8.0', '8.1', '8.2', '8.3', '8.4'];

            foreach ($majorVersions as $major) {
                $configPath = config_path("extensions/php/{$major}/versions.php");
                if (file_exists($configPath)) {
                    $config = require $configPath;
                    $allVersions[$major] = $config;
                }
            }

            return $allVersions;
        }

        // 返回指定主版本的配置
        $configPath = config_path("extensions/php/{$majorVersion}/versions.php");
        if (file_exists($configPath)) {
            return require $configPath;
        }

        return [];
    }

    /**
     * 获取所有PHP版本列表
     *
     * @return array
     */
    public function getAllPhpVersions(): array
    {
        $allVersions = [];
        $versionConfigs = $this->getPhpVersions();

        foreach ($versionConfigs as $config) {
            if (isset($config['versions'])) {
                $allVersions = array_merge($allVersions, $config['versions']);
            }
        }

        return array_unique($allVersions);
    }

    /**
     * 获取Composer版本配置
     *
     * @return array
     */
    public function getComposerVersions(): array
    {
        return config('mirror.composer.versions', []);
    }

    /**
     * 获取PECL扩展配置（按旧系统格式）
     *
     * @param string|null $extension 扩展名，为null时返回支持的扩展列表
     * @return array
     */
    public function getPeclConfig(string $extension = null): array
    {
        if ($extension === null) {
            return $this->getMirrorConfig('pecl.extensions', []);
        }

        $configPath = config_path("extensions/pecl/{$extension}.php");
        if (file_exists($configPath)) {
            return require $configPath;
        }

        return [];
    }

    /**
     * 获取PECL扩展版本列表
     *
     * @param string $extension 扩展名
     * @return array
     */
    public function getPeclExtensionVersions(string $extension): array
    {
        $config = $this->getPeclConfig($extension);
        return $config['all_versions'] ?? [];
    }

    /**
     * 获取GitHub扩展配置（按旧系统格式）
     *
     * @param string|null $extension 扩展名，为null时返回支持的扩展列表
     * @return array
     */
    public function getGithubExtensionConfig(string $extension = null): array
    {
        if ($extension === null) {
            return $this->getMirrorConfig('extensions.extensions', []);
        }

        $configPath = config_path("extensions/github/{$extension}.php");
        if (file_exists($configPath)) {
            return require $configPath;
        }

        return [];
    }

    /**
     * 获取GitHub扩展版本列表
     *
     * @param string $extension 扩展名
     * @return array
     */
    public function getGithubExtensionVersions(string $extension): array
    {
        $config = $this->getGithubExtensionConfig($extension);
        return $config['all_versions'] ?? [];
    }

    /**
     * 获取配置值
     *
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = self::CACHE_PREFIX . $key;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key, $default) {
            $config = SystemConfig::where('key', $key)->first();
            
            if (!$config) {
                return $default;
            }
            
            // 解析JSON值
            $value = json_decode($config->value, true);
            return $value !== null ? $value : $config->value;
        });
    }

    /**
     * 设置配置值
     *
     * @param string $key 配置键
     * @param mixed $value 配置值
     * @param string|null $description 配置描述
     * @return bool
     */
    public function set(string $key, $value, string $description = null): bool
    {
        try {
            // 如果是数组或对象，转换为JSON
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            
            SystemConfig::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'description' => $description,
                ]
            );
            
            // 清除缓存
            Cache::forget(self::CACHE_PREFIX . $key);
            
            Log::info("配置更新成功", ['key' => $key]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("配置更新失败", [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * 删除配置
     *
     * @param string $key 配置键
     * @return bool
     */
    public function delete(string $key): bool
    {
        try {
            SystemConfig::where('key', $key)->delete();
            Cache::forget(self::CACHE_PREFIX . $key);
            
            Log::info("配置删除成功", ['key' => $key]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("配置删除失败", [
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * 获取数据目录配置
     *
     * @return string
     */
    public function getDataDir(): string
    {
        return $this->get('system.data_dir', base_path('data'));
    }

    /**
     * 获取缓存目录配置
     *
     * @return string
     */
    public function getCacheDir(): string
    {
        return $this->get('system.cache_dir', storage_path('app/mirror-cache'));
    }

    /**
     * 获取服务器配置
     *
     * @return array
     */
    public function getServerConfig(): array
    {
        return $this->get('server', [
            'host' => '0.0.0.0',
            'port' => 8080,
            'public_url' => 'http://localhost:8080',
        ]);
    }

    /**
     * 获取同步配置
     *
     * @return array
     */
    public function getSyncConfig(): array
    {
        return $this->get('sync', [
            'interval' => 24,
            'max_retries' => 3,
            'retry_interval' => 300,
            'concurrent_downloads' => 5,
        ]);
    }

    /**
     * 获取日志配置
     *
     * @return array
     */
    public function getLogConfig(): array
    {
        return $this->get('log', [
            'enable_logging' => true,
            'log_level' => 'info',
            'log_rotation' => true,
            'max_log_size' => 10 * 1024 * 1024, // 10MB
            'max_log_files' => 10,
            'log_types' => [
                'system' => true,
                'access' => true,
                'error' => true,
                'sync' => true,
                'download' => true,
            ],
        ]);
    }

    /**
     * 获取安全配置
     *
     * @return array
     */
    public function getSecurityConfig(): array
    {
        return $this->get('security', [
            'enable_access_control' => false,
            'allowed_ips' => [],
            'rate_limit' => [
                'enabled' => true,
                'max_requests' => 100,
                'window_minutes' => 60,
            ],
        ]);
    }

    /**
     * 获取PHP配置
     *
     * @return array
     */
    public function getPhpConfig(): array
    {
        return $this->get('php', [
            'enabled' => true,
            'source' => 'https://www.php.net/distributions/',
            'pattern' => '/php-(\d+\.\d+\.\d+)\.tar\.gz/',
            'versions' => [],
        ]);
    }



    /**
     * 获取所有配置
     *
     * @return array
     */
    public function getAllConfigs(): array
    {
        $configs = SystemConfig::all();
        $result = [];
        
        foreach ($configs as $config) {
            $value = json_decode($config->value, true);
            $result[$config->key] = [
                'value' => $value !== null ? $value : $config->value,
                'description' => $config->description,
                'updated_at' => $config->updated_at,
            ];
        }
        
        return $result;
    }

    /**
     * 批量设置配置
     *
     * @param array $configs 配置数组
     * @return bool
     */
    public function setMultiple(array $configs): bool
    {
        try {
            foreach ($configs as $key => $data) {
                $value = $data['value'] ?? $data;
                $description = $data['description'] ?? null;
                
                $this->set($key, $value, $description);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("批量配置更新失败", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * 清除所有配置缓存
     *
     * @return void
     */
    public function clearCache(): void
    {
        $configs = SystemConfig::all();
        
        foreach ($configs as $config) {
            Cache::forget(self::CACHE_PREFIX . $config->key);
        }
        
        Log::info("配置缓存已清除");
    }
}
