<?php

/**
 * 自动加载器
 */
class Autoloader
{
    /**
     * 注册自动加载器
     */
    public static function register()
    {
        spl_autoload_register(function ($class) {
            // 检查是否是Mirror命名空间
            $prefix = 'Mirror\\';
            $len = strlen($prefix);

            if (strncmp($prefix, $class, $len) !== 0) {
                return false; // 不是我们的命名空间
            }

            // 获取相对类名
            $relativeClass = substr($class, $len);

            // 检查是否是测试类
            if (strpos($relativeClass, 'Tests\\') === 0) {
                // 测试类在tests目录
                $testClass = substr($relativeClass, 6); // 移除'Tests\'
                $file = ROOT_DIR . '/tests/' . str_replace('\\', '/', $testClass) . '.php';
            } else {
                // 普通类在src目录
                $file = ROOT_DIR . '/src/' . str_replace('\\', '/', $relativeClass) . '.php';
            }

            // 如果文件存在，则包含它
            if (file_exists($file)) {
                require $file;
                return true;
            }

            return false;
        });
    }
}
