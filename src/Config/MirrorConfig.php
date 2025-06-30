<?php

namespace Mirror\Config;

/**
 * 镜像配置管理类
 *
 * 注意：此类已被废弃，请使用 ConfigManager 类代替
 * @deprecated 使用 ConfigManager 类代替
 */
class MirrorConfig
{
    /**
     * 配置管理器
     *
     * @var ConfigManager
     */
    private $configManager;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->configManager = new ConfigManager();
    }

    /**
     * 获取PHP源码配置
     *
     * @return array
     */
    public function getPhpConfig()
    {
        return $this->configManager->getPhpConfig();
    }

    /**
     * 获取PECL扩展配置
     *
     * @return array
     */
    public function getPeclConfig()
    {
        return $this->configManager->getPeclConfig();
    }

    /**
     * 获取特定扩展配置
     *
     * @return array
     */
    public function getExtensionsConfig()
    {
        return $this->configManager->getExtensionsConfig();
    }

    /**
     * 获取Composer配置
     *
     * @return array
     */
    public function getComposerConfig()
    {
        return $this->configManager->getComposerConfig();
    }

    /**
     * 获取服务器配置
     *
     * @return array
     */
    public function getServerConfig()
    {
        return $this->configManager->getServerConfig();
    }

    /**
     * 获取清理配置
     *
     * @return array
     */
    public function getCleanupConfig()
    {
        return $this->configManager->getCleanupConfig();
    }

    /**
     * 获取完整配置
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->configManager->getMirrorConfig();
    }
}
