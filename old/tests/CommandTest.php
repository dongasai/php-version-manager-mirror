<?php

namespace Mirror\Tests;

use Mirror\Application;

/**
 * 命令测试
 */
class CommandTest
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

    public function testHelpCommand()
    {
        $command = $this->application->getCommand('help');
        $this->assertNotNull($command);
        
        ob_start();
        $exitCode = $command->execute([]);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Usage:', $output);
        $this->assertStringContainsString('Commands:', $output);
    }

    public function testStatusCommand()
    {
        $command = $this->application->getCommand('status');
        $this->assertNotNull($command);
        
        ob_start();
        $exitCode = $command->execute([]);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertIsString($output);
    }

    public function testConfigCommand()
    {
        $command = $this->application->getCommand('config');
        $this->assertNotNull($command);
        
        ob_start();
        $exitCode = $command->execute([]);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertIsString($output);
    }

    public function testServerCommand()
    {
        $command = $this->application->getCommand('server');
        $this->assertNotNull($command);
        
        // 测试server命令的help
        ob_start();
        $exitCode = $command->execute(['help']);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertIsString($output);
    }

    public function testSyncCommand()
    {
        $command = $this->application->getCommand('sync');
        $this->assertNotNull($command);
        
        // 测试sync命令的help
        ob_start();
        $exitCode = $command->execute(['help']);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertIsString($output);
    }

    public function testCleanCommand()
    {
        $command = $this->application->getCommand('clean');
        $this->assertNotNull($command);
        
        ob_start();
        $exitCode = $command->execute([]);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertIsString($output);
    }

    public function testCacheCommand()
    {
        $command = $this->application->getCommand('cache');
        $this->assertNotNull($command);
        
        ob_start();
        $exitCode = $command->execute(['status']);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertIsString($output);
    }

    public function testLogCommand()
    {
        $command = $this->application->getCommand('log');
        $this->assertNotNull($command);
        
        ob_start();
        $exitCode = $command->execute(['list']);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertIsString($output);
    }

    public function testMonitorCommand()
    {
        $command = $this->application->getCommand('monitor');
        $this->assertNotNull($command);
        
        ob_start();
        $exitCode = $command->execute(['status']);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertIsString($output);
    }

    public function testResourceCommand()
    {
        $command = $this->application->getCommand('resource');
        $this->assertNotNull($command);
        
        ob_start();
        $exitCode = $command->execute(['status']);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertIsString($output);
    }

    public function testSecurityCommand()
    {
        $command = $this->application->getCommand('security');
        $this->assertNotNull($command);
        
        ob_start();
        $exitCode = $command->execute(['status']);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertIsString($output);
    }

    public function testIntegrateCommand()
    {
        $command = $this->application->getCommand('integrate');
        $this->assertNotNull($command);
        
        ob_start();
        $exitCode = $command->execute(['status']);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertIsString($output);
    }

    public function testDiscoverCommand()
    {
        $command = $this->application->getCommand('discover');
        $this->assertNotNull($command);
        
        ob_start();
        $exitCode = $command->execute(['php']);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertIsString($output);
    }

    public function testUpdateConfigCommand()
    {
        $command = $this->application->getCommand('update-config');
        $this->assertNotNull($command);
        
        ob_start();
        $exitCode = $command->execute(['help']);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertIsString($output);
    }

    public function testSplitVersionsCommand()
    {
        $command = $this->application->getCommand('split-versions');
        $this->assertNotNull($command);
        
        ob_start();
        $exitCode = $command->execute(['help']);
        $output = ob_get_clean();
        
        $this->assertEquals(0, $exitCode);
        $this->assertIsString($output);
    }

    public function testAllCommandsExist()
    {
        $expectedCommands = [
            'help', 'status', 'server', 'sync', 'config', 'clean',
            'cache', 'log', 'monitor', 'resource', 'security',
            'integrate', 'discover', 'update-config', 'split-versions'
        ];

        $commands = $this->application->getCommands();
        
        foreach ($expectedCommands as $commandName) {
            $this->assertArrayHasKey($commandName, $commands, "Command '$commandName' should exist");
            $this->assertNotNull($commands[$commandName], "Command '$commandName' should not be null");
        }
    }

    public function testCommandInterfaces()
    {
        $commands = $this->application->getCommands();
        
        foreach ($commands as $name => $command) {
            $this->assertIsObject($command, "Command '$name' should be an object");
            $this->assertTrue(method_exists($command, 'execute'), "Command '$name' should have execute method");
            $this->assertTrue(method_exists($command, 'getName'), "Command '$name' should have getName method");
            $this->assertEquals($name, $command->getName(), "Command name should match");
        }
    }
}
