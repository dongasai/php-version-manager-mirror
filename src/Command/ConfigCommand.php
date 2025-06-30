<?php

namespace Mirror\Command;

use Mirror\Config\ConfigManager;

/**
 * 配置命令类
 */
class ConfigCommand extends AbstractCommand
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct('config', '管理镜像配置');
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
            return $this->showHelp();
        }

        $action = array_shift($args);

        switch ($action) {
            case 'get':
                return $this->getConfig($args);
            case 'set':
                return $this->setConfig($args);
            case 'list':
                return $this->listConfig($args);
            case 'edit':
                return $this->editConfig($args);
            case 'help':
            default:
                return $this->showHelp();
        }
    }

    /**
     * 显示帮助信息
     *
     * @return int 退出代码
     */
    private function showHelp()
    {
        echo "配置管理命令\n";
        echo "\n";
        echo "用法:\n";
        echo "  pvm-mirror config get <key>                 获取配置项\n";
        echo "  pvm-mirror config set <key> <value>         设置配置项\n";
        echo "  pvm-mirror config list [runtime|mirror]     列出配置\n";
        echo "  pvm-mirror config edit [runtime|mirror]     编辑配置文件\n";
        echo "\n";
        echo "示例:\n";
        echo "  pvm-mirror config get server.port           获取服务器端口\n";
        echo "  pvm-mirror config set server.port 8080      设置服务器端口\n";
        echo "  pvm-mirror config list runtime              列出运行时配置\n";
        echo "  pvm-mirror config edit mirror               编辑镜像配置文件\n";
        echo "\n";

        return 0;
    }

    /**
     * 获取配置项
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    private function getConfig(array $args)
    {
        if (empty($args)) {
            echo "错误: 请指定要获取的配置项\n";
            return 1;
        }

        $key = $args[0];
        $configManager = new ConfigManager();
        $allConfig = $configManager->getAllConfig();

        // 解析键路径
        $parts = explode('.', $key);
        $value = $allConfig;

        foreach ($parts as $part) {
            if (isset($value[$part])) {
                $value = $value[$part];
            } else {
                echo "错误: 配置项 '$key' 不存在\n";
                return 1;
            }
        }

        // 输出值
        if (is_array($value)) {
            echo json_encode($value, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo $value . "\n";
        }

        return 0;
    }

    /**
     * 设置配置项
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    private function setConfig(array $args)
    {
        if (count($args) < 2) {
            echo "错误: 请指定要设置的配置项和值\n";
            return 1;
        }

        $key = $args[0];
        $value = $args[1];
        $configManager = new ConfigManager();
        $allConfig = $configManager->getAllConfig();

        // 解析键路径
        $parts = explode('.', $key);
        $configType = $parts[0];

        // 确定配置类型
        if ($configType === 'runtime' || $configType === 'mirror') {
            array_shift($parts);
            $configKey = implode('.', $parts);

            // 更新配置
            $config = $allConfig[$configType];
            $this->updateNestedArray($config, $parts, $value);

            // 保存配置
            if ($configType === 'runtime') {
                $configManager->saveRuntimeConfig($config);
                echo "运行时配置已更新\n";
            } else {
                $configManager->saveMirrorConfig($config);
                echo "镜像配置已更新\n";
            }

            return 0;
        } else {
            echo "错误: 无效的配置类型 '$configType'，必须是 'runtime' 或 'mirror'\n";
            return 1;
        }
    }

    /**
     * 更新嵌套数组
     *
     * @param array &$array 要更新的数组
     * @param array $keys 键路径
     * @param mixed $value 新值
     */
    private function updateNestedArray(&$array, $keys, $value)
    {
        $key = array_shift($keys);

        if (empty($keys)) {
            // 尝试转换值类型
            if ($value === 'true') {
                $value = true;
            } elseif ($value === 'false') {
                $value = false;
            } elseif ($value === 'null') {
                $value = null;
            } elseif (is_numeric($value)) {
                if (strpos($value, '.') !== false) {
                    $value = (float) $value;
                } else {
                    $value = (int) $value;
                }
            }

            $array[$key] = $value;
        } else {
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $this->updateNestedArray($array[$key], $keys, $value);
        }
    }

    /**
     * 列出配置
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    private function listConfig(array $args)
    {
        $configType = isset($args[0]) ? $args[0] : 'all';
        $configManager = new ConfigManager();

        if ($configType === 'runtime') {
            $config = $configManager->getRuntimeConfig();
            echo "运行时配置:\n";
            echo json_encode($config, JSON_PRETTY_PRINT) . "\n";
        } elseif ($configType === 'mirror') {
            $config = $configManager->getMirrorConfig();
            echo "镜像配置:\n";
            echo json_encode($config, JSON_PRETTY_PRINT) . "\n";
        } else {
            $config = $configManager->getAllConfig();
            echo "所有配置:\n";
            echo json_encode($config, JSON_PRETTY_PRINT) . "\n";
        }

        return 0;
    }

    /**
     * 编辑配置文件
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    private function editConfig(array $args)
    {
        $configType = isset($args[0]) ? $args[0] : 'runtime';

        if ($configType === 'runtime') {
            $configFile = ROOT_DIR . '/config/runtime.php';
            echo "编辑运行时配置文件: $configFile\n";
        } elseif ($configType === 'mirror') {
            $configFile = ROOT_DIR . '/config/mirror.php';
            echo "编辑镜像配置文件: $configFile\n";
        } else {
            echo "错误: 无效的配置类型 '$configType'，必须是 'runtime' 或 'mirror'\n";
            return 1;
        }

        // 检查编辑器
        $editor = getenv('EDITOR');
        if (empty($editor)) {
            $editor = 'vi';
        }

        // 打开编辑器
        system("$editor $configFile", $returnCode);

        if ($returnCode !== 0) {
            echo "错误: 编辑器返回错误代码 $returnCode\n";
            return 1;
        }

        echo "配置文件已编辑\n";
        return 0;
    }
}
