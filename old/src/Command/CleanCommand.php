<?php

namespace Mirror\Command;

use Mirror\Mirror\PhpMirror;
use Mirror\Mirror\PeclMirror;
use Mirror\Mirror\ExtensionMirror;
use Mirror\Mirror\ComposerMirror;

/**
 * 清理命令类
 */
class CleanCommand extends AbstractCommand
{
    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct('clean', '清理过期镜像');
    }

    /**
     * 执行命令
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    public function execute(array $args = [])
    {
        echo "清理过期镜像...\n";

        // 加载配置
        $configs = $this->loadConfig();
        $mirrorConfig = $configs['mirror'];
        $runtimeConfig = $configs['runtime'];

        // 显示清理配置信息
        $cleanupConfig = $runtimeConfig['cleanup'] ?? [];
        echo "清理配置:\n";
        echo "  每个主版本保留的最新版本数量: " . ($cleanupConfig['keep_versions'] ?? 5) . "\n";
        echo "  最小保留天数: " . ($cleanupConfig['min_age'] ?? 30) . " 天\n";

        // 清理 PHP 源码包
        if (isset($mirrorConfig['php']['enabled']) && $mirrorConfig['php']['enabled']) {
            echo "\n清理 PHP 源码包...\n";
            $phpMirror = new PhpMirror();
            $phpMirror->clean($mirrorConfig['php']);
        } else {
            echo "\n跳过 PHP 源码包清理 (已禁用)\n";
        }

        // 清理 PECL 扩展包
        if (isset($mirrorConfig['pecl']['enabled']) && $mirrorConfig['pecl']['enabled']) {
            echo "\n清理 PECL 扩展包...\n";
            $peclMirror = new PeclMirror();
            $peclMirror->clean($mirrorConfig['pecl']);
        } else {
            echo "\n跳过 PECL 扩展包清理 (已禁用)\n";
        }

        // 清理特定扩展的 GitHub 源码
        $enabledExtensions = [];
        foreach ($mirrorConfig['extensions'] as $extension => $config) {
            if (isset($config['enabled']) && $config['enabled']) {
                $enabledExtensions[$extension] = $config;
            }
        }

        if (!empty($enabledExtensions)) {
            echo "\n清理特定扩展的 GitHub 源码...\n";
            $extensionMirror = new ExtensionMirror();
            $extensionMirror->clean($enabledExtensions);
        } else {
            echo "\n跳过特定扩展源码清理 (已禁用)\n";
        }

        // 清理 Composer 包
        if (isset($mirrorConfig['composer']['enabled']) && $mirrorConfig['composer']['enabled']) {
            echo "\n清理 Composer 包...\n";
            $composerMirror = new ComposerMirror();
            $composerMirror->clean($mirrorConfig['composer']);
        } else {
            echo "\n跳过 Composer 包清理 (已禁用)\n";
        }

        echo "\n清理完成\n";

        return 0;
    }
}
