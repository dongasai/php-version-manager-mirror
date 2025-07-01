<?php

/**
 * PHPUnit 测试引导文件
 */

// 定义根目录
define('ROOT_DIR', dirname(__DIR__));

// 尝试使用Composer自动加载器，如果不存在则使用内置自动加载器
if (file_exists(ROOT_DIR . '/vendor/autoload.php')) {
    require ROOT_DIR . '/vendor/autoload.php';
} else {
    // 包含内置自动加载器
    require ROOT_DIR . '/src/Autoloader.php';
    // 注册自动加载器
    Autoloader::register();
}

// 设置测试环境
putenv('PVM_MIRROR_ENV=testing');
putenv('PVM_MIRROR_DEBUG=false');

// 创建测试所需的目录
$testDirs = [
    ROOT_DIR . '/tests/tmp',
    ROOT_DIR . '/tests/tmp/data',
    ROOT_DIR . '/tests/tmp/logs',
    ROOT_DIR . '/tests/tmp/cache',
];

foreach ($testDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// 清理函数
register_shutdown_function(function () {
    // 清理测试临时文件
    $tmpDir = ROOT_DIR . '/tests/tmp';
    if (is_dir($tmpDir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmpDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        
        rmdir($tmpDir);
    }
});
