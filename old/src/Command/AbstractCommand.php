<?php

namespace Mirror\Command;

/**
 * 抽象命令类
 */
abstract class AbstractCommand implements CommandInterface
{
    /**
     * 命令名称
     *
     * @var string
     */
    protected $name;

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description;

    /**
     * 构造函数
     *
     * @param string $name 命令名称
     * @param string $description 命令描述
     */
    public function __construct($name, $description)
    {
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * 获取命令名称
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * 获取命令描述
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * 加载配置
     *
     * @return array
     */
    protected function loadConfig()
    {
        try {
            $configManager = new \Mirror\Config\ConfigManager();
            return $configManager->getAllConfig();
        } catch (\Exception $e) {
            echo "错误: 加载配置失败: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    /**
     * 获取镜像配置
     *
     * @return array
     */
    protected function getMirrorConfig()
    {
        try {
            $configManager = new \Mirror\Config\ConfigManager();
            return $configManager->getMirrorConfig();
        } catch (\Exception $e) {
            echo "错误: 加载镜像配置失败: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    /**
     * 获取运行时配置
     *
     * @return array
     */
    protected function getRuntimeConfig()
    {
        try {
            $configManager = new \Mirror\Config\ConfigManager();
            return $configManager->getRuntimeConfig();
        } catch (\Exception $e) {
            echo "错误: 加载运行时配置失败: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}
