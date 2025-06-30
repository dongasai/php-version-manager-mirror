<?php

namespace Mirror\Tests;

use PHPUnit\Framework\TestCase;
use Mirror\Utils\FileUtils;
use Mirror\Utils\StringUtils;
use Mirror\Utils\ArrayUtils;

/**
 * 工具类测试
 */
class UtilsTest extends TestCase
{
    protected function setUp(): void
    {
        // 定义ROOT_DIR常量（如果未定义）
        if (!defined('ROOT_DIR')) {
            define('ROOT_DIR', dirname(__DIR__));
        }
    }

    public function testFileUtilsFormatSize()
    {
        $this->assertEquals('1.00 KB', FileUtils::formatSize(1024));
        $this->assertEquals('1.00 MB', FileUtils::formatSize(1024 * 1024));
        $this->assertEquals('1.00 GB', FileUtils::formatSize(1024 * 1024 * 1024));
        $this->assertEquals('500 B', FileUtils::formatSize(500));
        $this->assertEquals('0 B', FileUtils::formatSize(0));
    }

    public function testFileUtilsEnsureDirectory()
    {
        $testDir = ROOT_DIR . '/tests/tmp/test_dir';
        
        // 确保目录不存在
        if (is_dir($testDir)) {
            rmdir($testDir);
        }
        
        $result = FileUtils::ensureDirectory($testDir);
        $this->assertTrue($result);
        $this->assertDirectoryExists($testDir);
        
        // 清理
        rmdir($testDir);
    }

    public function testFileUtilsGetMimeType()
    {
        $this->assertEquals('text/plain', FileUtils::getMimeType('test.txt'));
        $this->assertEquals('application/json', FileUtils::getMimeType('test.json'));
        $this->assertEquals('application/gzip', FileUtils::getMimeType('test.tar.gz'));
        $this->assertEquals('application/octet-stream', FileUtils::getMimeType('test.unknown'));
    }

    public function testFileUtilsIsWritable()
    {
        $testFile = ROOT_DIR . '/tests/tmp/writable_test.txt';
        
        // 创建测试文件
        file_put_contents($testFile, 'test');
        
        $this->assertTrue(FileUtils::isWritable($testFile));
        
        // 清理
        unlink($testFile);
    }

    public function testStringUtilsSlugify()
    {
        $this->assertEquals('hello-world', StringUtils::slugify('Hello World'));
        $this->assertEquals('php-8-3-8', StringUtils::slugify('PHP 8.3.8'));
        $this->assertEquals('test-string', StringUtils::slugify('Test@String!'));
        $this->assertEquals('', StringUtils::slugify(''));
    }

    public function testStringUtilsStartsWith()
    {
        $this->assertTrue(StringUtils::startsWith('hello world', 'hello'));
        $this->assertFalse(StringUtils::startsWith('hello world', 'world'));
        $this->assertTrue(StringUtils::startsWith('PHP', 'P'));
        $this->assertFalse(StringUtils::startsWith('', 'test'));
    }

    public function testStringUtilsEndsWith()
    {
        $this->assertTrue(StringUtils::endsWith('hello world', 'world'));
        $this->assertFalse(StringUtils::endsWith('hello world', 'hello'));
        $this->assertTrue(StringUtils::endsWith('test.php', '.php'));
        $this->assertFalse(StringUtils::endsWith('', 'test'));
    }

    public function testStringUtilsContains()
    {
        $this->assertTrue(StringUtils::contains('hello world', 'lo wo'));
        $this->assertFalse(StringUtils::contains('hello world', 'xyz'));
        $this->assertTrue(StringUtils::contains('PHP 8.3.8', '8.3'));
        $this->assertFalse(StringUtils::contains('', 'test'));
    }

    public function testStringUtilsTruncate()
    {
        $this->assertEquals('Hello...', StringUtils::truncate('Hello World', 8));
        $this->assertEquals('Hello World', StringUtils::truncate('Hello World', 20));
        $this->assertEquals('Hello***', StringUtils::truncate('Hello World', 8, '***'));
        $this->assertEquals('', StringUtils::truncate('', 10));
    }

    public function testArrayUtilsGet()
    {
        $array = ['key1' => 'value1', 'nested' => ['key2' => 'value2']];
        
        $this->assertEquals('value1', ArrayUtils::get($array, 'key1'));
        $this->assertEquals('value2', ArrayUtils::get($array, 'nested.key2'));
        $this->assertEquals('default', ArrayUtils::get($array, 'nonexistent', 'default'));
        $this->assertNull(ArrayUtils::get($array, 'nonexistent'));
    }

    public function testArrayUtilsSet()
    {
        $array = [];
        
        ArrayUtils::set($array, 'key1', 'value1');
        $this->assertEquals('value1', $array['key1']);
        
        ArrayUtils::set($array, 'nested.key2', 'value2');
        $this->assertEquals('value2', $array['nested']['key2']);
    }

    public function testArrayUtilsHas()
    {
        $array = ['key1' => 'value1', 'nested' => ['key2' => 'value2']];
        
        $this->assertTrue(ArrayUtils::has($array, 'key1'));
        $this->assertTrue(ArrayUtils::has($array, 'nested.key2'));
        $this->assertFalse(ArrayUtils::has($array, 'nonexistent'));
        $this->assertFalse(ArrayUtils::has($array, 'nested.nonexistent'));
    }

    public function testArrayUtilsFlatten()
    {
        $array = [
            'key1' => 'value1',
            'nested' => [
                'key2' => 'value2',
                'deep' => [
                    'key3' => 'value3'
                ]
            ]
        ];
        
        $flattened = ArrayUtils::flatten($array);
        
        $this->assertEquals('value1', $flattened['key1']);
        $this->assertEquals('value2', $flattened['nested.key2']);
        $this->assertEquals('value3', $flattened['nested.deep.key3']);
    }

    public function testArrayUtilsOnly()
    {
        $array = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];
        $result = ArrayUtils::only($array, ['key1', 'key3']);
        
        $this->assertArrayHasKey('key1', $result);
        $this->assertArrayHasKey('key3', $result);
        $this->assertArrayNotHasKey('key2', $result);
        $this->assertEquals('value1', $result['key1']);
        $this->assertEquals('value3', $result['key3']);
    }

    public function testArrayUtilsExcept()
    {
        $array = ['key1' => 'value1', 'key2' => 'value2', 'key3' => 'value3'];
        $result = ArrayUtils::except($array, ['key2']);
        
        $this->assertArrayHasKey('key1', $result);
        $this->assertArrayHasKey('key3', $result);
        $this->assertArrayNotHasKey('key2', $result);
        $this->assertEquals('value1', $result['key1']);
        $this->assertEquals('value3', $result['key3']);
    }

    public function testArrayUtilsWhere()
    {
        $array = [
            ['name' => 'John', 'age' => 25],
            ['name' => 'Jane', 'age' => 30],
            ['name' => 'Bob', 'age' => 25]
        ];
        
        $result = ArrayUtils::where($array, 'age', 25);
        
        $this->assertCount(2, $result);
        $this->assertEquals('John', $result[0]['name']);
        $this->assertEquals('Bob', $result[2]['name']);
    }
}
