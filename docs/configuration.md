# 配置指南

本文档详细介绍了 PHP Version Manager Mirror 的配置选项和最佳实践。

## 配置文件结构

```
config/
├── runtime.php         # 运行时配置
├── mirror.php          # 镜像内容配置
├── download.php        # 下载配置
└── extensions/         # 扩展配置目录
    ├── composer/
    ├── pecl/
    └── php/
```

## 运行时配置 (runtime.php)

### 服务器配置

```php
<?php
return [
    'server' => [
        'host' => env('PVM_MIRROR_HOST', '0.0.0.0'),
        'port' => env('PVM_MIRROR_PORT', 34403),
        'max_connections' => env('PVM_MIRROR_MAX_CONNECTIONS', 100),
        'timeout' => env('PVM_MIRROR_TIMEOUT', 30),
        'enable_https' => env('PVM_MIRROR_ENABLE_HTTPS', false),
        'ssl_cert' => env('PVM_MIRROR_SSL_CERT_PATH', ''),
        'ssl_key' => env('PVM_MIRROR_SSL_KEY_PATH', ''),
    ],
    
    'paths' => [
        'data' => env('PVM_MIRROR_DATA_DIR', ROOT_DIR . '/data'),
        'logs' => env('PVM_MIRROR_LOG_DIR', ROOT_DIR . '/logs'),
        'cache' => env('PVM_MIRROR_CACHE_DIR', ROOT_DIR . '/cache'),
        'public' => ROOT_DIR . '/public',
    ],
    
    'logging' => [
        'level' => env('PVM_MIRROR_LOG_LEVEL', 'info'),
        'max_files' => 30,
        'max_size' => '10MB',
        'enable_access_log' => env('PVM_MIRROR_ENABLE_ACCESS_LOG', true),
    ],
    
    'cache' => [
        'enabled' => true,
        'max_size' => env('PVM_MIRROR_CACHE_SIZE', 104857600), // 100MB
        'ttl' => env('PVM_MIRROR_CACHE_TTL', 3600), // 1小时
        'cleanup_interval' => 300, // 5分钟
    ],
    
    'security' => [
        'enable_rate_limit' => env('PVM_MIRROR_ENABLE_RATE_LIMIT', true),
        'rate_limit_requests' => env('PVM_MIRROR_RATE_LIMIT_REQUESTS', 100),
        'rate_limit_window' => env('PVM_MIRROR_RATE_LIMIT_WINDOW', 3600),
        'allowed_ips' => [], // 空数组表示允许所有IP
        'blocked_ips' => [],
    ],
];
```

### 配置选项说明

#### 服务器配置
- `host`: 监听地址，`0.0.0.0` 表示监听所有接口
- `port`: 监听端口，默认 34403
- `max_connections`: 最大并发连接数
- `timeout`: 请求超时时间（秒）
- `enable_https`: 是否启用 HTTPS
- `ssl_cert/ssl_key`: SSL 证书和私钥路径

#### 路径配置
- `data`: 数据存储目录
- `logs`: 日志文件目录
- `cache`: 缓存文件目录
- `public`: Web 界面文件目录

#### 日志配置
- `level`: 日志级别 (debug, info, warning, error)
- `max_files`: 保留的日志文件数量
- `max_size`: 单个日志文件最大大小
- `enable_access_log`: 是否启用访问日志

#### 缓存配置
- `enabled`: 是否启用缓存
- `max_size`: 缓存最大大小（字节）
- `ttl`: 缓存生存时间（秒）
- `cleanup_interval`: 缓存清理间隔（秒）

#### 安全配置
- `enable_rate_limit`: 是否启用速率限制
- `rate_limit_requests`: 限制时间窗口内的请求数
- `rate_limit_window`: 限制时间窗口（秒）
- `allowed_ips`: 允许的IP地址列表
- `blocked_ips`: 禁止的IP地址列表

## 镜像配置 (mirror.php)

```php
<?php
return [
    'sources' => [
        'php' => [
            'enabled' => true,
            'official_url' => 'https://www.php.net/distributions/',
            'sync_interval' => 24, // 小时
            'versions' => [
                '8.3' => ['enabled' => true, 'priority' => 1],
                '8.2' => ['enabled' => true, 'priority' => 2],
                '8.1' => ['enabled' => true, 'priority' => 3],
                '8.0' => ['enabled' => true, 'priority' => 4],
                '7.4' => ['enabled' => true, 'priority' => 5],
                '7.3' => ['enabled' => false, 'priority' => 6],
                '7.2' => ['enabled' => false, 'priority' => 7],
                '7.1' => ['enabled' => false, 'priority' => 8],
            ],
        ],
        
        'composer' => [
            'enabled' => true,
            'official_url' => 'https://getcomposer.org/download/',
            'sync_interval' => 12, // 小时
            'versions' => [
                'latest' => ['enabled' => true, 'priority' => 1],
                'stable' => ['enabled' => true, 'priority' => 2],
                'preview' => ['enabled' => false, 'priority' => 3],
            ],
        ],
        
        'pecl' => [
            'enabled' => true,
            'official_url' => 'https://pecl.php.net/',
            'sync_interval' => 24, // 小时
            'popular_extensions' => [
                'redis', 'memcached', 'mongodb', 'xdebug', 'opcache',
                'imagick', 'gd', 'curl', 'json', 'mbstring'
            ],
        ],
    ],
    
    'sync' => [
        'max_retries' => env('PVM_MIRROR_MAX_RETRIES', 3),
        'retry_interval' => env('PVM_MIRROR_RETRY_INTERVAL', 300), // 5分钟
        'concurrent_downloads' => 5,
        'verify_checksums' => true,
        'cleanup_old_versions' => true,
        'keep_versions' => 10, // 保留的版本数量
    ],
    
    'monitoring' => [
        'enabled' => env('PVM_MIRROR_ENABLE_MONITORING', true),
        'check_interval' => env('PVM_MIRROR_MONITORING_INTERVAL', 60), // 秒
        'health_check_url' => '/ping',
        'metrics' => [
            'download_count' => true,
            'response_time' => true,
            'error_rate' => true,
            'disk_usage' => true,
        ],
    ],
];
```

## 下载配置 (download.php)

```php
<?php
return [
    'download' => [
        'user_agent' => 'PVM-Mirror/1.0 (+https://github.com/dongasai/php-version-manager-mirror)',
        'timeout' => 300, // 5分钟
        'max_redirects' => 5,
        'verify_ssl' => true,
        'chunk_size' => 8192, // 8KB
        'resume_downloads' => true,
        'parallel_downloads' => 3,
    ],
    
    'mirrors' => [
        'fallback_mirrors' => [
            'https://pvm.2sxo.com',
            'https://mirrors.aliyun.com/php',
        ],
        'speed_test' => [
            'enabled' => true,
            'test_file_size' => 1024, // 1KB
            'timeout' => 10, // 秒
            'cache_duration' => 3600, // 1小时
        ],
    ],
    
    'validation' => [
        'verify_checksums' => true,
        'verify_signatures' => false, // 需要 GPG 支持
        'min_file_size' => 1024, // 1KB
        'max_file_size' => 1073741824, // 1GB
    ],
];
```

## 环境变量

### 基础环境变量

```bash
# 服务配置
PVM_MIRROR_HOST=0.0.0.0
PVM_MIRROR_PORT=34403
PVM_MIRROR_PUBLIC_URL=http://localhost:34403

# 目录配置
PVM_MIRROR_DATA_DIR=/app/data
PVM_MIRROR_LOG_DIR=/app/logs
PVM_MIRROR_CACHE_DIR=/app/cache

# 日志配置
PVM_MIRROR_LOG_LEVEL=info
PVM_MIRROR_ENABLE_ACCESS_LOG=true

# 缓存配置
PVM_MIRROR_CACHE_SIZE=104857600
PVM_MIRROR_CACHE_TTL=3600

# 同步配置
PVM_MIRROR_SYNC_INTERVAL=24
PVM_MIRROR_MAX_RETRIES=3
PVM_MIRROR_RETRY_INTERVAL=300

# 安全配置
PVM_MIRROR_ENABLE_RATE_LIMIT=true
PVM_MIRROR_RATE_LIMIT_REQUESTS=100
PVM_MIRROR_RATE_LIMIT_WINDOW=3600

# 监控配置
PVM_MIRROR_ENABLE_MONITORING=true
PVM_MIRROR_MONITORING_INTERVAL=60
```

### Docker 环境变量

在 Docker 中使用环境变量：

```bash
docker run -d \
  --name pvm-mirror \
  -p 34403:34403 \
  -e PVM_MIRROR_HOST=0.0.0.0 \
  -e PVM_MIRROR_PORT=34403 \
  -e PVM_MIRROR_LOG_LEVEL=debug \
  -e PVM_MIRROR_CACHE_SIZE=209715200 \
  -v pvm_mirror_data:/app/data \
  -v pvm_mirror_logs:/app/logs \
  ghcr.io/dongasai/php-version-manager-mirror:latest
```

## 配置最佳实践

### 生产环境配置

```php
// config/runtime.php - 生产环境
return [
    'server' => [
        'host' => '0.0.0.0',
        'port' => 34403,
        'max_connections' => 200,
        'timeout' => 60,
        'enable_https' => true,
        'ssl_cert' => '/etc/ssl/certs/pvm-mirror.crt',
        'ssl_key' => '/etc/ssl/private/pvm-mirror.key',
    ],
    
    'logging' => [
        'level' => 'warning', // 减少日志输出
        'max_files' => 90,    // 保留更多日志
        'max_size' => '50MB', // 更大的日志文件
    ],
    
    'cache' => [
        'max_size' => 1073741824, // 1GB
        'ttl' => 7200,            // 2小时
    ],
    
    'security' => [
        'enable_rate_limit' => true,
        'rate_limit_requests' => 1000,
        'rate_limit_window' => 3600,
    ],
];
```

### 开发环境配置

```php
// config/runtime.php - 开发环境
return [
    'server' => [
        'host' => '127.0.0.1',
        'port' => 34403,
        'max_connections' => 50,
        'timeout' => 30,
        'enable_https' => false,
    ],
    
    'logging' => [
        'level' => 'debug',   // 详细日志
        'max_files' => 10,
        'max_size' => '10MB',
    ],
    
    'cache' => [
        'max_size' => 52428800, // 50MB
        'ttl' => 300,           // 5分钟
    ],
    
    'security' => [
        'enable_rate_limit' => false, // 开发时禁用限制
    ],
];
```

## 配置验证

### 验证配置文件

```bash
# 检查配置语法
php -l config/runtime.php
php -l config/mirror.php

# 测试配置加载
./bin/pvm-mirror config --validate
```

### 配置测试

```bash
# 测试服务器配置
./bin/pvm-mirror server test

# 测试镜像配置
./bin/pvm-mirror sync --dry-run

# 测试下载配置
./bin/pvm-mirror download --test
```

## 故障排除

### 常见配置问题

1. **端口冲突**
   - 检查端口是否被占用：`netstat -tlnp | grep 34403`
   - 修改配置中的端口号

2. **权限问题**
   - 确保数据目录可写：`chmod 755 data logs cache`
   - 检查 SSL 证书权限

3. **内存不足**
   - 调整缓存大小：`PVM_MIRROR_CACHE_SIZE`
   - 增加 PHP 内存限制

4. **网络问题**
   - 检查防火墙设置
   - 验证 DNS 解析
   - 测试网络连接

### 配置调试

```bash
# 查看当前配置
./bin/pvm-mirror config --show

# 检查配置差异
./bin/pvm-mirror config --diff

# 重置配置
./bin/pvm-mirror config --reset
```
