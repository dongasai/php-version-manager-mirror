<?php

namespace Mirror\Tests;

use PHPUnit\Framework\TestCase;
use Mirror\Application;
use Mirror\Config\ConfigManager;
use Mirror\Server\HttpServer;

/**
 * 集成测试
 * 测试整个系统的端到端功能
 */
class IntegrationTest extends TestCase
{
    private $application;
    private $configManager;
    private $testPort;
    private $testDataDir;

    protected function setUp(): void
    {
        // 定义ROOT_DIR常量（如果未定义）
        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', dirname(__DIR__));
        }
        
        $this->application = new Application();
        $this->configManager = new ConfigManager();
        $this->testPort = 34404; // 使用不同的端口避免冲突
        $this->testDataDir = ROOT_DIR . '/tests/tmp/integration';
        
        // 创建测试目录
        $this->ensureTestDirectories();
        
        // 设置测试环境变量
        putenv('PVM_MIRROR_PORT=' . $this->testPort);
        putenv('PVM_MIRROR_DATA_DIR=' . $this->testDataDir);
        putenv('PVM_MIRROR_LOG_LEVEL=debug');
    }

    protected function tearDown(): void
    {
        // 清理测试环境
        $this->cleanupTestDirectories();
    }

    private function ensureTestDirectories()
    {
        $dirs = [
            $this->testDataDir,
            $this->testDataDir . '/logs',
            $this->testDataDir . '/cache',
            $this->testDataDir . '/downloads'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    private function cleanupTestDirectories()
    {
        if (is_dir($this->testDataDir)) {
            $this->removeDirectory($this->testDataDir);
        }
    }

    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function testFullApplicationWorkflow()
    {
        // 1. 测试应用程序初始化
        $this->assertInstanceOf(Application::class, $this->application);
        
        // 2. 测试配置加载
        $config = $this->configManager->getRuntimeConfig();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('server', $config);
        
        // 3. 测试命令执行
        ob_start();
        $exitCode = $this->application->run(['status']);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('PVM Mirror Status', $output);
    }

    public function testConfigurationManagement()
    {
        // 测试配置管理功能
        $config = $this->configManager->getRuntimeConfig();
        
        // 验证基本配置结构
        $this->assertArrayHasKey('server', $config);
        $this->assertArrayHasKey('paths', $config);
        $this->assertArrayHasKey('logging', $config);
        $this->assertArrayHasKey('cache', $config);
        
        // 验证服务器配置
        $serverConfig = $config['server'];
        $this->assertArrayHasKey('host', $serverConfig);
        $this->assertArrayHasKey('port', $serverConfig);
        $this->assertArrayHasKey('timeout', $serverConfig);
        
        // 验证路径配置
        $pathsConfig = $config['paths'];
        $this->assertArrayHasKey('data', $pathsConfig);
        $this->assertArrayHasKey('logs', $pathsConfig);
        $this->assertArrayHasKey('cache', $pathsConfig);
    }

    public function testMirrorConfiguration()
    {
        // 测试镜像配置
        $mirrorConfig = $this->configManager->getMirrorConfig();
        
        $this->assertIsArray($mirrorConfig);
        $this->assertArrayHasKey('sources', $mirrorConfig);
        
        $sources = $mirrorConfig['sources'];
        $this->assertArrayHasKey('php', $sources);
        $this->assertArrayHasKey('composer', $sources);
        
        // 验证PHP源配置
        $phpSource = $sources['php'];
        $this->assertArrayHasKey('enabled', $phpSource);
        $this->assertArrayHasKey('official_url', $phpSource);
        $this->assertArrayHasKey('versions', $phpSource);
    }

    public function testCommandLineInterface()
    {
        $testCases = [
            ['help', 'Usage:', 0],
            ['status', 'PVM Mirror Status', 0],
            ['config', 'Configuration', 0],
            ['invalid-command', 'Unknown command', 1],
        ];
        
        foreach ($testCases as [$command, $expectedOutput, $expectedExitCode]) {
            ob_start();
            $exitCode = $this->application->run([$command]);
            $output = ob_get_clean();
            
            $this->assertEquals($expectedExitCode, $exitCode, "Command '$command' should return exit code $expectedExitCode");
            $this->assertStringContainsString($expectedOutput, $output, "Command '$command' should contain '$expectedOutput' in output");
        }
    }

    public function testFileSystemOperations()
    {
        // 测试文件系统操作
        $testFile = $this->testDataDir . '/test.txt';
        $testContent = 'Test content for integration test';
        
        // 写入文件
        file_put_contents($testFile, $testContent);
        $this->assertFileExists($testFile);
        
        // 读取文件
        $readContent = file_get_contents($testFile);
        $this->assertEquals($testContent, $readContent);
        
        // 删除文件
        unlink($testFile);
        $this->assertFileDoesNotExist($testFile);
    }

    public function testDirectoryStructure()
    {
        // 验证项目目录结构
        $requiredDirs = [
            'src',
            'config',
            'bin',
            'docker',
            'tests',
            'docs'
        ];
        
        foreach ($requiredDirs as $dir) {
            $path = ROOT_DIR . '/' . $dir;
            $this->assertDirectoryExists($path, "Directory '$dir' should exist");
        }
        
        // 验证关键文件
        $requiredFiles = [
            'bin/pvm-mirror',
            'composer.json',
            'README.md',
            'LICENSE',
            'CHANGELOG.md'
        ];
        
        foreach ($requiredFiles as $file) {
            $path = ROOT_DIR . '/' . $file;
            $this->assertFileExists($path, "File '$file' should exist");
        }
    }

    public function testConfigurationFiles()
    {
        // 验证配置文件
        $configFiles = [
            'config/runtime.php',
            'config/mirror.php',
            'config/download.php'
        ];
        
        foreach ($configFiles as $file) {
            $path = ROOT_DIR . '/' . $file;
            $this->assertFileExists($path, "Config file '$file' should exist");
            
            // 验证PHP语法
            $output = [];
            $returnCode = 0;
            exec("php -l $path 2>&1", $output, $returnCode);
            $this->assertEquals(0, $returnCode, "Config file '$file' should have valid PHP syntax");
        }
    }

    public function testEnvironmentVariables()
    {
        // 测试环境变量处理
        $testEnvVar = 'PVM_MIRROR_TEST_VAR';
        $testValue = 'test_value_123';
        
        putenv("$testEnvVar=$testValue");
        
        // 验证环境变量设置
        $this->assertEquals($testValue, getenv($testEnvVar));
        
        // 清理
        putenv($testEnvVar);
    }

    public function testErrorHandling()
    {
        // 测试错误处理
        ob_start();
        $exitCode = $this->application->run(['nonexistent-command']);
        $output = ob_get_clean();
        
        $this->assertNotEquals(0, $exitCode);
        $this->assertStringContainsString('Unknown command', $output);
    }

    public function testLoggingFunctionality()
    {
        // 测试日志功能
        $logDir = $this->testDataDir . '/logs';
        $this->assertDirectoryExists($logDir);
        
        // 创建测试日志文件
        $logFile = $logDir . '/test.log';
        $logMessage = 'Test log message ' . time();
        
        file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
        
        $this->assertFileExists($logFile);
        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString($logMessage, $logContent);
    }

    public function testCacheFunctionality()
    {
        // 测试缓存功能
        $cacheDir = $this->testDataDir . '/cache';
        $this->assertDirectoryExists($cacheDir);
        
        // 创建测试缓存文件
        $cacheFile = $cacheDir . '/test_cache.json';
        $cacheData = ['test' => 'data', 'timestamp' => time()];
        
        file_put_contents($cacheFile, json_encode($cacheData));
        
        $this->assertFileExists($cacheFile);
        $readData = json_decode(file_get_contents($cacheFile), true);
        $this->assertEquals($cacheData, $readData);
    }

    public function testSystemRequirements()
    {
        // 验证系统要求
        $this->assertTrue(version_compare(PHP_VERSION, '7.1.0', '>='), 'PHP version should be 7.1 or higher');
        
        // 检查必需的PHP扩展
        $requiredExtensions = ['curl', 'json', 'mbstring'];
        foreach ($requiredExtensions as $extension) {
            $this->assertTrue(extension_loaded($extension), "PHP extension '$extension' should be loaded");
        }
        
        // 检查函数可用性
        $requiredFunctions = ['file_get_contents', 'file_put_contents', 'curl_init', 'json_encode', 'json_decode'];
        foreach ($requiredFunctions as $function) {
            $this->assertTrue(function_exists($function), "Function '$function' should be available");
        }
    }
}
