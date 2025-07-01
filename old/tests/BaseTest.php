<?php

namespace Mirror\Tests;

/**
 * 基础测试类
 */
class BaseTest
{
    public function setUp()
    {
        // 定义ROOT_DIR常量（如果未定义）
        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', dirname(__DIR__));
        }
    }
    
    // 简单的断言方法
    protected function assertEquals($expected, $actual, $message = '')
    {
        if ($expected !== $actual) {
            throw new \Exception($message ?: "Expected '$expected', got '$actual'");
        }
    }
    
    protected function assertInstanceOf($expected, $actual, $message = '')
    {
        if (!($actual instanceof $expected)) {
            throw new \Exception($message ?: "Expected instance of '$expected'");
        }
    }
    
    protected function assertIsArray($actual, $message = '')
    {
        if (!is_array($actual)) {
            throw new \Exception($message ?: "Expected array");
        }
    }
    
    protected function assertNotEmpty($actual, $message = '')
    {
        if (empty($actual)) {
            throw new \Exception($message ?: "Expected non-empty value");
        }
    }
    
    protected function assertIsString($actual, $message = '')
    {
        if (!is_string($actual)) {
            throw new \Exception($message ?: "Expected string");
        }
    }
    
    protected function assertArrayHasKey($key, $array, $message = '')
    {
        if (!array_key_exists($key, $array)) {
            throw new \Exception($message ?: "Expected array to have key '$key'");
        }
    }
    
    protected function assertStringContainsString($needle, $haystack, $message = '')
    {
        if (strpos($haystack, $needle) === false) {
            throw new \Exception($message ?: "Expected string to contain '$needle'");
        }
    }
    
    protected function assertNotEquals($expected, $actual, $message = '')
    {
        if ($expected === $actual) {
            throw new \Exception($message ?: "Expected values to be different");
        }
    }
    
    protected function assertNotNull($actual, $message = '')
    {
        if ($actual === null) {
            throw new \Exception($message ?: "Expected non-null value");
        }
    }
    
    protected function assertIsObject($actual, $message = '')
    {
        if (!is_object($actual)) {
            throw new \Exception($message ?: "Expected object");
        }
    }
    
    protected function assertNull($actual, $message = '')
    {
        if ($actual !== null) {
            throw new \Exception($message ?: "Expected null value");
        }
    }
    
    protected function assertTrue($actual, $message = '')
    {
        if ($actual !== true) {
            throw new \Exception($message ?: "Expected true");
        }
    }
    
    protected function assertDirectoryExists($path, $message = '')
    {
        if (!is_dir($path)) {
            throw new \Exception($message ?: "Expected directory '$path' to exist");
        }
    }
    
    protected function assertFileExists($path, $message = '')
    {
        if (!file_exists($path)) {
            throw new \Exception($message ?: "Expected file '$path' to exist");
        }
    }
    
    protected function assertMatchesRegularExpression($pattern, $string, $message = '')
    {
        if (!preg_match($pattern, $string)) {
            throw new \Exception($message ?: "Expected string to match pattern '$pattern'");
        }
    }
    
    protected function assertLessThan($expected, $actual, $message = '')
    {
        if ($actual >= $expected) {
            throw new \Exception($message ?: "Expected '$actual' to be less than '$expected'");
        }
    }
}
