<?php

namespace Mirror\Tests;

use PHPUnit\Framework\TestCase;
use Mirror\Application;
use Mirror\Config\ConfigManager;
use Mirror\Cache\CacheManager;

/**
 * 性能测试
 */
class PerformanceTest extends TestCase
{
    private $application;

    protected function setUp(): void
    {
        // 定义ROOT_DIR常量（如果未定义）
        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', dirname(__DIR__));
        }
        
        $this->application = new Application();
    }

    public function testApplicationStartupTime()
    {
        $startTime = microtime(true);
        
        // 创建多个应用程序实例
        for ($i = 0; $i < 10; $i++) {
            $app = new Application();
            $commands = $app->getCommands();
            $this->assertNotEmpty($commands);
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // 应该在1秒内完成10次初始化
        $this->assertLessThan(1.0, $duration, 'Application startup should be fast');
    }

    public function testCommandExecutionTime()
    {
        $commands = ['help', 'status', 'config'];
        
        foreach ($commands as $commandName) {
            $startTime = microtime(true);
            
            ob_start();
            $command = $this->application->getCommand($commandName);
            $this->assertNotNull($command);
            
            $exitCode = $command->execute([]);
            ob_end_clean();
            
            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            
            // 每个命令应该在5秒内完成
            $this->assertLessThan(5.0, $duration, "Command '$commandName' should execute quickly");
            $this->assertEquals(0, $exitCode, "Command '$commandName' should succeed");
        }
    }

    public function testConfigLoadingTime()
    {
        $startTime = microtime(true);
        
        // 加载配置多次
        for ($i = 0; $i < 5; $i++) {
            try {
                $config = new ConfigManager();
                $this->assertInstanceOf(ConfigManager::class, $config);
            } catch (\Exception $e) {
                // 配置加载可能失败，但不应该太慢
                $this->assertIsString($e->getMessage());
            }
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // 配置加载应该很快
        $this->assertLessThan(2.0, $duration, 'Config loading should be fast');
    }

    public function testCacheOperationTime()
    {
        $startTime = microtime(true);
        
        try {
            $cache = new CacheManager();
            
            // 测试缓存操作
            for ($i = 0; $i < 10; $i++) {
                $key = "test_key_$i";
                $value = "test_value_$i";
                
                $cache->set($key, $value);
                $retrieved = $cache->get($key);
                
                if ($retrieved !== null) {
                    $this->assertEquals($value, $retrieved);
                }
            }
        } catch (\Exception $e) {
            // 缓存操作可能失败，但记录时间
            $this->assertIsString($e->getMessage());
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // 缓存操作应该很快
        $this->assertLessThan(3.0, $duration, 'Cache operations should be fast');
    }

    public function testMemoryUsage()
    {
        $initialMemory = memory_get_usage();
        
        // 创建多个应用程序实例
        $apps = [];
        for ($i = 0; $i < 5; $i++) {
            $apps[] = new Application();
        }
        
        $peakMemory = memory_get_peak_usage();
        $memoryIncrease = $peakMemory - $initialMemory;
        
        // 内存增长应该合理（小于50MB）
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, 'Memory usage should be reasonable');
        
        // 清理
        unset($apps);
    }

    public function testConcurrentCommandExecution()
    {
        $startTime = microtime(true);
        
        // 模拟并发执行（在单线程环境中快速连续执行）
        $results = [];
        for ($i = 0; $i < 3; $i++) {
            ob_start();
            $exitCode = $this->application->run(['pvm-mirror', 'status']);
            $output = ob_get_clean();
            
            $results[] = [
                'exitCode' => $exitCode,
                'output' => $output
            ];
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // 并发执行应该在合理时间内完成
        $this->assertLessThan(10.0, $duration, 'Concurrent execution should be efficient');
        
        // 所有执行都应该成功
        foreach ($results as $i => $result) {
            $this->assertEquals(0, $result['exitCode'], "Execution $i should succeed");
            $this->assertIsString($result['output'], "Execution $i should produce output");
        }
    }

    public function testFileSystemOperations()
    {
        $startTime = microtime(true);
        
        // 测试文件系统操作
        $testDirs = ['data', 'logs', 'cache'];
        foreach ($testDirs as $dir) {
            $path = ROOT_DIR . '/' . $dir;
            
            // 检查目录是否存在或可创建
            if (!is_dir($path)) {
                $created = @mkdir($path, 0755, true);
                if ($created) {
                    // 清理测试创建的目录
                    @rmdir($path);
                }
            } else {
                $this->assertDirectoryExists($path);
            }
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // 文件系统操作应该很快
        $this->assertLessThan(1.0, $duration, 'File system operations should be fast');
    }

    public function testAutoloaderPerformance()
    {
        $startTime = microtime(true);
        
        // 测试自动加载器性能
        $classes = [
            'Mirror\\Application',
            'Mirror\\Config\\ConfigManager',
            'Mirror\\Cache\\CacheManager',
            'Mirror\\Command\\HelpCommand',
            'Mirror\\Command\\StatusCommand'
        ];
        
        foreach ($classes as $className) {
            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);
                $this->assertTrue($reflection->isInstantiable() || $reflection->isAbstract());
            }
        }
        
        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        
        // 类加载应该很快
        $this->assertLessThan(0.5, $duration, 'Class loading should be fast');
    }

    public function testResourceCleanup()
    {
        $initialMemory = memory_get_usage();
        
        // 创建和销毁资源
        for ($i = 0; $i < 10; $i++) {
            $app = new Application();
            $commands = $app->getCommands();
            
            // 执行一些操作
            ob_start();
            $app->run(['pvm-mirror', 'help']);
            ob_end_clean();
            
            // 显式清理
            unset($app, $commands);
        }
        
        // 强制垃圾回收
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        $finalMemory = memory_get_usage();
        $memoryDiff = $finalMemory - $initialMemory;
        
        // 内存应该得到合理清理（允许一些增长）
        $this->assertLessThan(10 * 1024 * 1024, $memoryDiff, 'Memory should be cleaned up properly');
    }
}
