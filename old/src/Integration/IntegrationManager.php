<?php

namespace Mirror\Integration;

use Mirror\Config\ConfigManager;
use Mirror\Monitor\MonitorManager;

/**
 * 集成管理器类
 * 
 * 用于实现与PVM的集成
 */
class IntegrationManager
{
    /**
     * 配置管理器
     *
     * @var ConfigManager
     */
    private $configManager;

    /**
     * 监控管理器
     *
     * @var MonitorManager
     */
    private $monitorManager;

    /**
     * PVM配置目录
     *
     * @var string
     */
    private $pvmConfigDir;

    /**
     * 镜像配置文件路径
     *
     * @var string
     */
    private $mirrorConfigFile;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->configManager = new ConfigManager();
        $this->monitorManager = new MonitorManager();

        // 设置PVM配置目录
        $homeDir = getenv('HOME');
        if (empty($homeDir)) {
            $homeDir = '/root';
        }
        $this->pvmConfigDir = $homeDir . '/.pvm/config';
        $this->mirrorConfigFile = $this->pvmConfigDir . '/mirrors.php';
    }

    /**
     * 配置PVM使用本地镜像
     *
     * @return bool 是否成功
     */
    public function configurePvm()
    {
        // 获取运行时配置
        $runtimeConfig = $this->configManager->getRuntimeConfig();
        $serverConfig = $runtimeConfig['server'] ?? [];

        // 获取公开URL
        $publicUrl = $serverConfig['public_url'] ?? 'http://localhost:34403';

        // 确保URL末尾没有斜杠
        $publicUrl = rtrim($publicUrl, '/');

        // 创建镜像配置
        $mirrorConfig = [
            'php' => [
                'official' => 'https://www.php.net/distributions',
                'mirrors' => [
                    'local' => $publicUrl . '/php',
                ],
                'default' => 'local',
            ],
            'pecl' => [
                'official' => 'https://pecl.php.net/get',
                'mirrors' => [
                    'local' => $publicUrl . '/pecl',
                ],
                'default' => 'local',
            ],
            'extensions' => [
                'redis' => [
                    'official' => 'https://github.com/phpredis/phpredis/archive/refs/tags',
                    'mirrors' => [
                        'local' => $publicUrl . '/extensions/redis',
                    ],
                    'default' => 'local',
                ],
                'memcached' => [
                    'official' => 'https://github.com/php-memcached-dev/php-memcached/archive/refs/tags',
                    'mirrors' => [
                        'local' => $publicUrl . '/extensions/memcached',
                    ],
                    'default' => 'local',
                ],
                'xdebug' => [
                    'official' => 'https://github.com/xdebug/xdebug/archive/refs/tags',
                    'mirrors' => [
                        'local' => $publicUrl . '/extensions/xdebug',
                    ],
                    'default' => 'local',
                ],
                'mongodb' => [
                    'official' => 'https://github.com/mongodb/mongo-php-driver/archive/refs/tags',
                    'mirrors' => [
                        'local' => $publicUrl . '/extensions/mongodb',
                    ],
                    'default' => 'local',
                ],
                'imagick' => [
                    'official' => 'https://github.com/Imagick/imagick/archive/refs/tags',
                    'mirrors' => [
                        'local' => $publicUrl . '/extensions/imagick',
                    ],
                    'default' => 'local',
                ],
                'swoole' => [
                    'official' => 'https://github.com/swoole/swoole-src/archive/refs/tags',
                    'mirrors' => [
                        'local' => $publicUrl . '/extensions/swoole',
                    ],
                    'default' => 'local',
                ],
            ],
            'composer' => [
                'official' => 'https://getcomposer.org/download',
                'mirrors' => [
                    'local' => $publicUrl . '/composer',
                ],
                'default' => 'local',
            ],
        ];

        // 确保PVM配置目录存在
        if (!is_dir($this->pvmConfigDir)) {
            if (!mkdir($this->pvmConfigDir, 0755, true)) {
                return false;
            }
        }

        // 保存镜像配置
        $content = "<?php\n\n// 镜像配置文件\n// 由 PVM Mirror 自动生成，可以手动修改\n\nreturn " . var_export($mirrorConfig, true) . ";\n";
        return file_put_contents($this->mirrorConfigFile, $content) !== false;
    }

    /**
     * 检查镜像健康状态
     *
     * @return array 健康状态
     */
    public function checkMirrorHealth()
    {
        // 获取健康状态
        return $this->monitorManager->checkHealth();
    }

    /**
     * 自动切换镜像
     *
     * @param string $type 镜像类型
     * @param string $fallbackMirror 备用镜像
     * @return bool 是否成功
     */
    public function switchMirror($type, $fallbackMirror = 'official')
    {
        // 检查镜像健康状态
        $health = $this->checkMirrorHealth();

        // 如果镜像状态正常，则不需要切换
        if ($health['mirror'] === 'normal') {
            return true;
        }

        // 如果镜像配置文件不存在，则无法切换
        if (!file_exists($this->mirrorConfigFile)) {
            return false;
        }

        // 加载镜像配置
        $mirrorConfig = require $this->mirrorConfigFile;

        // 检查镜像类型是否存在
        if (!isset($mirrorConfig[$type])) {
            return false;
        }

        // 检查备用镜像是否存在
        if ($fallbackMirror !== 'official' && !isset($mirrorConfig[$type]['mirrors'][$fallbackMirror])) {
            $fallbackMirror = 'official';
        }

        // 设置默认镜像
        $mirrorConfig[$type]['default'] = $fallbackMirror;

        // 保存镜像配置
        $content = "<?php\n\n// 镜像配置文件\n// 由 PVM Mirror 自动生成，可以手动修改\n\nreturn " . var_export($mirrorConfig, true) . ";\n";
        return file_put_contents($this->mirrorConfigFile, $content) !== false;
    }

    /**
     * 自动切换所有镜像
     *
     * @param string $fallbackMirror 备用镜像
     * @return bool 是否成功
     */
    public function switchAllMirrors($fallbackMirror = 'official')
    {
        $success = true;
        $success &= $this->switchMirror('php', $fallbackMirror);
        $success &= $this->switchMirror('pecl', $fallbackMirror);
        $success &= $this->switchMirror('composer', $fallbackMirror);

        // 切换扩展镜像
        if (file_exists($this->mirrorConfigFile)) {
            $mirrorConfig = require $this->mirrorConfigFile;
            if (isset($mirrorConfig['extensions'])) {
                foreach (array_keys($mirrorConfig['extensions']) as $extension) {
                    $success &= $this->switchExtensionMirror($extension, $fallbackMirror);
                }
            }
        }

        return $success;
    }

    /**
     * 切换扩展镜像
     *
     * @param string $extension 扩展名称
     * @param string $fallbackMirror 备用镜像
     * @return bool 是否成功
     */
    public function switchExtensionMirror($extension, $fallbackMirror = 'official')
    {
        // 如果镜像配置文件不存在，则无法切换
        if (!file_exists($this->mirrorConfigFile)) {
            return false;
        }

        // 加载镜像配置
        $mirrorConfig = require $this->mirrorConfigFile;

        // 检查扩展是否存在
        if (!isset($mirrorConfig['extensions'][$extension])) {
            return false;
        }

        // 检查备用镜像是否存在
        if ($fallbackMirror !== 'official' && !isset($mirrorConfig['extensions'][$extension]['mirrors'][$fallbackMirror])) {
            $fallbackMirror = 'official';
        }

        // 设置默认镜像
        $mirrorConfig['extensions'][$extension]['default'] = $fallbackMirror;

        // 保存镜像配置
        $content = "<?php\n\n// 镜像配置文件\n// 由 PVM Mirror 自动生成，可以手动修改\n\nreturn " . var_export($mirrorConfig, true) . ";\n";
        return file_put_contents($this->mirrorConfigFile, $content) !== false;
    }

    /**
     * 获取PVM镜像配置
     *
     * @return array|null 镜像配置
     */
    public function getPvmMirrorConfig()
    {
        if (file_exists($this->mirrorConfigFile)) {
            return require $this->mirrorConfigFile;
        }

        return null;
    }

    /**
     * 检查PVM是否已配置使用本地镜像
     *
     * @return bool 是否已配置
     */
    public function isPvmConfigured()
    {
        // 如果镜像配置文件不存在，则未配置
        if (!file_exists($this->mirrorConfigFile)) {
            return false;
        }

        // 加载镜像配置
        $mirrorConfig = require $this->mirrorConfigFile;

        // 获取运行时配置
        $runtimeConfig = $this->configManager->getRuntimeConfig();
        $serverConfig = $runtimeConfig['server'] ?? [];

        // 获取公开URL
        $publicUrl = $serverConfig['public_url'] ?? 'http://localhost:34403';
        $publicUrl = rtrim($publicUrl, '/');

        // 检查PHP镜像是否配置为本地镜像
        if (!isset($mirrorConfig['php']['mirrors']['local']) || 
            $mirrorConfig['php']['mirrors']['local'] !== $publicUrl . '/php' ||
            $mirrorConfig['php']['default'] !== 'local') {
            return false;
        }

        return true;
    }
}
