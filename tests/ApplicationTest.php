<?php

namespace Mirror\Tests;

use PHPUnit\Framework\TestCase;
use Mirror\Application;

/**
 * 应用程序测试
 */
class ApplicationTest extends TestCase
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

    public function testApplicationCreation()
    {
        $this->assertInstanceOf(Application::class, $this->application);
    }

    public function testGetVersion()
    {
        $version = $this->application->getVersion();
        $this->assertIsString($version);
        $this->assertNotEmpty($version);
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+/', $version);
    }

    public function testGetCommands()
    {
        $commands = $this->application->getCommands();
        $this->assertIsArray($commands);
        $this->assertNotEmpty($commands);
        
        // 检查必要的命令是否存在
        $expectedCommands = ['help', 'status', 'server', 'sync', 'config'];
        foreach ($expectedCommands as $command) {
            $this->assertArrayHasKey($command, $commands);
        }
    }

    public function testRunWithHelpCommand()
    {
        // 捕获输出
        ob_start();
        $exitCode = $this->application->run(['help']);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Usage:', $output);
        $this->assertStringContainsString('Commands:', $output);
    }

    public function testRunWithInvalidCommand()
    {
        // 捕获输出
        ob_start();
        $exitCode = $this->application->run(['invalid-command']);
        $output = ob_get_clean();
        
        $this->assertNotEquals(0, $exitCode);
        $this->assertStringContainsString('Unknown command', $output);
    }

    public function testRunWithStatusCommand()
    {
        // 捕获输出
        ob_start();
        $exitCode = $this->application->run(['status']);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('PVM Mirror Status', $output);
    }

    public function testGetCommandInstance()
    {
        $helpCommand = $this->application->getCommand('help');
        $this->assertNotNull($helpCommand);
        $this->assertIsObject($helpCommand);
    }

    public function testGetInvalidCommandInstance()
    {
        $invalidCommand = $this->application->getCommand('invalid-command');
        $this->assertNull($invalidCommand);
    }

    public function testApplicationConstants()
    {
        $this->assertTrue(defined('ROOT_DIR'));
        $this->assertIsString(ROOT_DIR);
        $this->assertDirectoryExists(ROOT_DIR);
    }

    public function testApplicationDirectories()
    {
        $requiredDirs = ['src', 'config', 'bin'];
        
        foreach ($requiredDirs as $dir) {
            $path = ROOT_DIR . '/' . $dir;
            $this->assertDirectoryExists($path, "Directory $dir should exist");
        }
    }

    public function testApplicationFiles()
    {
        $requiredFiles = [
            'bin/pvm-mirror',
            'composer.json',
            'README.md'
        ];
        
        foreach ($requiredFiles as $file) {
            $path = ROOT_DIR . '/' . $file;
            $this->assertFileExists($path, "File $file should exist");
        }
    }
}
