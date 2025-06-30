<?php

namespace Mirror\Tests;

use PHPUnit\Framework\TestCase;
use Mirror\Config\ConfigManager;

/**
 * 配置管理测试
 */
class ConfigTest extends TestCase
{
    private $configManager;

    protected function setUp(): void
    {
        // 定义ROOT_DIR常量（如果未定义）
        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', dirname(__DIR__));
        }
        
        $this->configManager = new ConfigManager();
    }

    public function testConfigManagerCreation()
    {
        $this->assertInstanceOf(ConfigManager::class, $this->configManager);
    }

    public function testGetRuntimeConfig()
    {
        $config = $this->configManager->getRuntimeConfig();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('server', $config);
    }

    public function testGetMirrorConfig()
    {
        $config = $this->configManager->getMirrorConfig();
        $this->assertIsArray($config);
    }

    public function testGetDataDir()
    {
        $dataDir = $this->configManager->getDataDir();
        $this->assertIsString($dataDir);
        $this->assertNotEmpty($dataDir);
    }

    public function testGetLogDir()
    {
        $logDir = $this->configManager->getLogDir();
        $this->assertIsString($logDir);
        $this->assertNotEmpty($logDir);
    }

    public function testGetCacheDir()
    {
        $cacheDir = $this->configManager->getCacheDir();
        $this->assertIsString($cacheDir);
        $this->assertNotEmpty($cacheDir);
    }
}
