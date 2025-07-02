<?php

namespace App\Services;

/**
 * 镜像配置服务
 * 
 * 专门用于管理硬编码的镜像配置，提供统一的配置访问接口
 */
class MirrorConfigService
{
    /**
     * 获取镜像主配置
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
     * 获取PHP配置
     *
     * @return array
     */
    public function getPhpConfig(): array
    {
        return $this->getMirrorConfig('php', []);
    }

    /**
     * 获取所有PHP版本
     *
     * @return array
     */
    public function getAllPhpVersions(): array
    {
        $phpConfig = $this->getPhpConfig();
        $versions = $phpConfig['versions'] ?? [];
        
        $allVersions = [];
        foreach ($versions as $majorVersion => $config) {
            if (is_array($config) && isset($config['versions'])) {
                $allVersions = array_merge($allVersions, $config['versions']);
            }
        }
        
        return array_unique($allVersions);
    }

    /**
     * 获取指定主版本的PHP版本
     *
     * @param string $majorVersion 主版本号 (如: 8.3)
     * @return array
     */
    public function getPhpVersionsByMajor(string $majorVersion): array
    {
        $phpConfig = $this->getPhpConfig();
        $versions = $phpConfig['versions'] ?? [];
        
        return $versions[$majorVersion]['versions'] ?? [];
    }

    /**
     * 获取支持的PHP主版本列表
     *
     * @return array
     */
    public function getPhpMajorVersions(): array
    {
        return $this->getMirrorConfig('php.major_versions', []);
    }

    /**
     * 获取PECL配置
     *
     * @return array
     */
    public function getPeclConfig(): array
    {
        return $this->getMirrorConfig('pecl', []);
    }

    /**
     * 获取支持的PECL扩展列表
     *
     * @return array
     */
    public function getPeclExtensions(): array
    {
        return $this->getMirrorConfig('pecl.extensions', []);
    }

    /**
     * 获取指定PECL扩展的配置
     *
     * @param string $extension 扩展名
     * @return array
     */
    public function getPeclExtensionConfig(string $extension): array
    {
        $configPath = config_path("mirror/pecl/{$extension}.php");
        if (file_exists($configPath)) {
            return require $configPath;
        }
        
        return [];
    }

    /**
     * 获取指定PECL扩展的版本列表
     *
     * @param string $extension 扩展名
     * @return array
     */
    public function getPeclExtensionVersions(string $extension): array
    {
        $config = $this->getPeclExtensionConfig($extension);
        return $config['versions'] ?? [];
    }

    /**
     * 获取GitHub扩展配置
     *
     * @return array
     */
    public function getGithubExtensionConfig(): array
    {
        return $this->getMirrorConfig('extensions', []);
    }

    /**
     * 获取支持的GitHub扩展列表
     *
     * @return array
     */
    public function getGithubExtensions(): array
    {
        return $this->getMirrorConfig('extensions.extensions', []);
    }

    /**
     * 获取指定GitHub扩展的配置
     *
     * @param string $extension 扩展名
     * @return array
     */
    public function getGithubExtensionDetail(string $extension): array
    {
        $configPath = config_path("mirror/extensions/{$extension}.php");
        if (file_exists($configPath)) {
            return require $configPath;
        }
        
        return [];
    }

    /**
     * 获取指定GitHub扩展的版本列表
     *
     * @param string $extension 扩展名
     * @return array
     */
    public function getGithubExtensionVersions(string $extension): array
    {
        $config = $this->getGithubExtensionDetail($extension);
        return $config['versions'] ?? [];
    }

    /**
     * 获取Composer配置
     *
     * @return array
     */
    public function getComposerConfig(): array
    {
        return $this->getMirrorConfig('composer', []);
    }

    /**
     * 获取Composer版本列表
     *
     * @return array
     */
    public function getComposerVersions(): array
    {
        $configPath = config_path('mirror/composer/versions.php');
        if (file_exists($configPath)) {
            $config = require $configPath;
            return $config['versions'] ?? [];
        }
        
        return [];
    }

    /**
     * 检查扩展是否被支持
     *
     * @param string $type 类型 (pecl, github)
     * @param string $extension 扩展名
     * @return bool
     */
    public function isExtensionSupported(string $type, string $extension): bool
    {
        switch ($type) {
            case 'pecl':
                return in_array($extension, $this->getPeclExtensions());
            case 'github':
                return in_array($extension, $this->getGithubExtensions());
            default:
                return false;
        }
    }

    /**
     * 获取镜像统计信息
     *
     * @return array
     */
    public function getMirrorStats(): array
    {
        return [
            'php' => [
                'major_versions' => count($this->getPhpMajorVersions()),
                'total_versions' => count($this->getAllPhpVersions()),
                'enabled' => $this->getMirrorConfig('php.enabled', false),
            ],
            'pecl' => [
                'total_extensions' => count($this->getPeclExtensions()),
                'enabled' => $this->getMirrorConfig('pecl.enabled', false),
            ],
            'github' => [
                'total_extensions' => count($this->getGithubExtensions()),
                'enabled' => $this->getMirrorConfig('extensions.enabled', false),
            ],
            'composer' => [
                'total_versions' => count($this->getComposerVersions()),
                'enabled' => $this->getMirrorConfig('composer.enabled', false),
            ],
        ];
    }

    /**
     * 获取所有启用的镜像类型
     *
     * @return array
     */
    public function getEnabledMirrorTypes(): array
    {
        $types = [];
        
        if ($this->getMirrorConfig('php.enabled', false)) {
            $types[] = 'php';
        }
        
        if ($this->getMirrorConfig('pecl.enabled', false)) {
            $types[] = 'pecl';
        }
        
        if ($this->getMirrorConfig('extensions.enabled', false)) {
            $types[] = 'github';
        }
        
        if ($this->getMirrorConfig('composer.enabled', false)) {
            $types[] = 'composer';
        }
        
        return $types;
    }
}
