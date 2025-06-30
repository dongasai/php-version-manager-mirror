<?php

namespace Mirror\Command;

use Mirror\Service\VersionDiscoveryService;

/**
 * 版本发现命令类
 */
class DiscoverCommand extends AbstractCommand
{
    private $discoveryService;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct('discover', '发现可用版本');
        $this->discoveryService = new VersionDiscoveryService();
    }

    /**
     * 执行命令
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    public function execute(array $args = [])
    {
        if (empty($args)) {
            return $this->discoverAll();
        }

        $type = $args[0];
        $target = isset($args[1]) ? $args[1] : null;

        switch ($type) {
            case 'php':
                return $this->discoverPhp();

            case 'pecl':
                return $this->discoverPecl($target);

            case 'github':
            case 'ext':
                return $this->discoverGithub($target);

            default:
                echo "错误: 未知的发现类型 '$type'\n";
                echo "可用类型: php, pecl, github/ext\n";
                echo "\n使用示例:\n";
                echo "  ./bin/pvm-mirror discover              # 发现所有版本\n";
                echo "  ./bin/pvm-mirror discover php          # 发现PHP版本\n";
                echo "  ./bin/pvm-mirror discover pecl         # 发现所有PECL扩展版本\n";
                echo "  ./bin/pvm-mirror discover pecl redis   # 发现指定PECL扩展版本\n";
                echo "  ./bin/pvm-mirror discover github       # 发现所有GitHub扩展版本\n";
                echo "  ./bin/pvm-mirror discover ext swoole   # 发现指定GitHub扩展版本\n";
                return 1;
        }
    }

    /**
     * 发现所有版本
     *
     * @return int 退出代码
     */
    private function discoverAll()
    {
        echo "开始发现所有可用版本...\n\n";

        $versions = $this->discoveryService->discoverAllVersions();
        
        if (empty($versions['php']) && empty($versions['pecl']) && empty($versions['github'])) {
            echo "错误: 无法发现任何版本信息\n";
            return 1;
        }

        echo $this->discoveryService->formatVersionsForDisplay($versions);
        
        echo "\n版本发现完成\n";
        echo "使用 'pvm-mirror update-config' 命令将发现的版本更新到配置文件\n";
        
        return 0;
    }

    /**
     * 发现PHP版本
     *
     * @return int 退出代码
     */
    private function discoverPhp()
    {
        echo "开始发现 PHP 版本...\n\n";

        $versions = $this->discoveryService->discoverPhpVersions();
        
        if (empty($versions)) {
            echo "错误: 无法发现PHP版本信息\n";
            return 1;
        }

        echo "发现的 PHP 版本:\n";
        foreach ($versions as $version) {
            echo "  - $version\n";
        }
        
        echo "\n总计: " . count($versions) . " 个版本\n";
        
        return 0;
    }

    /**
     * 发现PECL扩展版本
     *
     * @param string|null $extensionName 扩展名
     * @return int 退出代码
     */
    private function discoverPecl($extensionName = null)
    {
        if ($extensionName) {
            echo "开始发现 PECL 扩展 '$extensionName' 的版本...\n\n";
        } else {
            echo "开始发现所有 PECL 扩展版本...\n\n";
        }

        $extensions = $this->discoveryService->discoverPeclVersions($extensionName);
        
        if (empty($extensions)) {
            if ($extensionName) {
                echo "错误: 无法发现扩展 '$extensionName' 的版本信息\n";
            } else {
                echo "错误: 无法发现PECL扩展版本信息\n";
            }
            return 1;
        }

        echo "发现的 PECL 扩展版本:\n";
        foreach ($extensions as $extension => $versions) {
            echo "  $extension:\n";
            foreach ($versions as $version) {
                echo "    - $version\n";
            }
            echo "    总计: " . count($versions) . " 个版本\n\n";
        }
        
        return 0;
    }

    /**
     * 发现GitHub扩展版本
     *
     * @param string|null $extensionName 扩展名
     * @return int 退出代码
     */
    private function discoverGithub($extensionName = null)
    {
        if ($extensionName) {
            echo "开始发现 GitHub 扩展 '$extensionName' 的版本...\n\n";
        } else {
            echo "开始发现所有 GitHub 扩展版本...\n\n";
        }

        $extensions = $this->discoveryService->discoverGithubVersions($extensionName);
        
        if (empty($extensions)) {
            if ($extensionName) {
                echo "错误: 无法发现扩展 '$extensionName' 的版本信息\n";
            } else {
                echo "错误: 无法发现GitHub扩展版本信息\n";
            }
            return 1;
        }

        echo "发现的 GitHub 扩展版本:\n";
        foreach ($extensions as $extension => $versions) {
            echo "  $extension:\n";
            foreach ($versions as $version) {
                echo "    - $version\n";
            }
            echo "    总计: " . count($versions) . " 个版本\n\n";
        }
        
        return 0;
    }

    /**
     * 获取命令帮助信息
     *
     * @return string 帮助信息
     */
    public function getHelp()
    {
        return "版本发现命令\n\n" .
               "用法:\n" .
               "  discover [类型] [目标]\n\n" .
               "参数:\n" .
               "  类型    发现类型: php, pecl, github/ext\n" .
               "  目标    指定目标（仅对 pecl 和 github 有效）\n\n" .
               "示例:\n" .
               "  discover              发现所有版本\n" .
               "  discover php          发现PHP版本\n" .
               "  discover pecl         发现所有PECL扩展版本\n" .
               "  discover pecl redis   发现指定PECL扩展版本\n" .
               "  discover github       发现所有GitHub扩展版本\n" .
               "  discover ext swoole   发现指定GitHub扩展版本\n";
    }
}
