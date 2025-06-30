# 使用示例和教程

本文档提供了 PHP Version Manager Mirror 的实用示例和教程，帮助用户快速上手。

## 快速开始

### 1. Docker 快速启动

最简单的启动方式：

```bash
# 拉取并运行
docker run -d \
  --name pvm-mirror \
  -p 34403:34403 \
  ghcr.io/dongasai/php-version-manager-mirror:latest

# 验证服务
curl http://localhost:34403/ping
```

### 2. 源码快速启动

```bash
# 下载源码
git clone https://github.com/dongasai/php-version-manager-mirror.git
cd php-version-manager-mirror

# 启动服务
./bin/pvm-mirror server start

# 查看状态
./bin/pvm-mirror status
```

## 基础使用教程

### 命令行基础操作

```bash
# 查看帮助
./bin/pvm-mirror help

# 查看版本
./bin/pvm-mirror --version

# 查看状态
./bin/pvm-mirror status

# 查看配置
./bin/pvm-mirror config

# 启动服务器
./bin/pvm-mirror server start

# 停止服务器
./bin/pvm-mirror server stop

# 重启服务器
./bin/pvm-mirror server restart

# 同步镜像内容
./bin/pvm-mirror sync

# 同步特定源
./bin/pvm-mirror sync --source=php

# 强制同步
./bin/pvm-mirror sync --force
```

### Web 界面使用

1. **访问管理界面**
   ```
   http://localhost:34403
   ```

2. **查看系统状态**
   - 服务器运行状态
   - 内存和磁盘使用情况
   - 缓存统计信息

3. **管理镜像内容**
   - 查看可用版本
   - 手动触发同步
   - 下载统计

4. **配置管理**
   - 修改运行时配置
   - 调整同步设置
   - 查看日志

## 高级配置示例

### 1. 生产环境配置

```bash
# 创建生产环境配置
mkdir -p /opt/pvm-mirror/{data,logs,cache,config}

# 复制配置文件
cp config/* /opt/pvm-mirror/config/

# 修改生产配置
cat > /opt/pvm-mirror/config/runtime.php << 'EOF'
<?php
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
    'paths' => [
        'data' => '/opt/pvm-mirror/data',
        'logs' => '/opt/pvm-mirror/logs',
        'cache' => '/opt/pvm-mirror/cache',
    ],
    'logging' => [
        'level' => 'warning',
        'max_files' => 90,
        'max_size' => '50MB',
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
EOF

# 启动生产服务
./bin/pvm-mirror server start --config=/opt/pvm-mirror/config
```

### 2. 开发环境配置

```bash
# 开发环境变量
export PVM_MIRROR_HOST=127.0.0.1
export PVM_MIRROR_PORT=34403
export PVM_MIRROR_LOG_LEVEL=debug
export PVM_MIRROR_CACHE_SIZE=52428800  # 50MB
export PVM_MIRROR_ENABLE_RATE_LIMIT=false

# 启动开发服务
./bin/pvm-mirror server start
```

### 3. Docker Compose 配置

```yaml
# docker-compose.yml
version: '3.8'

services:
  pvm-mirror:
    image: ghcr.io/dongasai/php-version-manager-mirror:latest
    container_name: pvm-mirror
    ports:
      - "34403:34403"
    environment:
      - PVM_MIRROR_HOST=0.0.0.0
      - PVM_MIRROR_PORT=34403
      - PVM_MIRROR_LOG_LEVEL=info
      - PVM_MIRROR_CACHE_SIZE=209715200  # 200MB
    volumes:
      - pvm_mirror_data:/app/data
      - pvm_mirror_logs:/app/logs
      - pvm_mirror_cache:/app/cache
      - ./config:/app/config:ro
    restart: unless-stopped
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:34403/ping"]
      interval: 30s
      timeout: 10s
      retries: 3

volumes:
  pvm_mirror_data:
  pvm_mirror_logs:
  pvm_mirror_cache:
```

## API 使用示例

### 1. 基础 API 调用

```bash
# 获取系统状态
curl -X GET http://localhost:34403/api/status

# 获取可用版本
curl -X GET http://localhost:34403/api/versions

# 获取特定版本信息
curl -X GET http://localhost:34403/api/versions/8.3.8

# 获取下载统计
curl -X GET http://localhost:34403/api/downloads

# 启动同步
curl -X POST http://localhost:34403/api/sync/start \
  -H "Content-Type: application/json" \
  -d '{"sources": ["php"], "force": false}'

# 获取缓存统计
curl -X GET http://localhost:34403/api/cache/stats

# 清空缓存
curl -X DELETE http://localhost:34403/api/cache/clear \
  -H "Content-Type: application/json" \
  -d '{"type": "all"}'
```

### 2. PHP 客户端示例

```php
<?php
// 简单的 PVM Mirror 客户端
class PvmMirrorClient
{
    private $baseUrl;
    
    public function __construct($baseUrl = 'http://localhost:34403/api')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }
    
    public function getStatus()
    {
        return $this->request('GET', '/status');
    }
    
    public function getVersions($type = null, $limit = 50)
    {
        $params = array_filter(['type' => $type, 'limit' => $limit]);
        $query = $params ? '?' . http_build_query($params) : '';
        return $this->request('GET', '/versions' . $query);
    }
    
    public function getVersion($version)
    {
        return $this->request('GET', '/versions/' . urlencode($version));
    }
    
    public function startSync($sources = [], $force = false)
    {
        return $this->request('POST', '/sync/start', [
            'sources' => $sources,
            'force' => $force
        ]);
    }
    
    public function getSyncStatus()
    {
        return $this->request('GET', '/sync/status');
    }
    
    public function getCacheStats()
    {
        return $this->request('GET', '/cache/stats');
    }
    
    public function clearCache($type = 'all')
    {
        return $this->request('DELETE', '/cache/clear', ['type' => $type]);
    }
    
    private function request($method, $path, $data = null)
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30,
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception("HTTP Error $httpCode: $response");
        }
        
        return json_decode($response, true);
    }
}

// 使用示例
try {
    $client = new PvmMirrorClient();
    
    // 获取状态
    $status = $client->getStatus();
    echo "服务状态: " . $status['data']['status'] . "\n";
    
    // 获取版本列表
    $versions = $client->getVersions('stable', 5);
    echo "可用版本数: " . count($versions['data']['versions']) . "\n";
    
    // 启动同步
    $sync = $client->startSync(['php'], false);
    echo "同步任务ID: " . $sync['data']['task_id'] . "\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
}
```

### 3. JavaScript 客户端示例

```javascript
// PVM Mirror JavaScript 客户端
class PvmMirrorClient {
    constructor(baseUrl = 'http://localhost:34403/api') {
        this.baseUrl = baseUrl.replace(/\/$/, '');
    }
    
    async getStatus() {
        return this.request('GET', '/status');
    }
    
    async getVersions(type = null, limit = 50) {
        const params = new URLSearchParams();
        if (type) params.append('type', type);
        if (limit) params.append('limit', limit);
        
        const query = params.toString() ? `?${params}` : '';
        return this.request('GET', `/versions${query}`);
    }
    
    async getVersion(version) {
        return this.request('GET', `/versions/${encodeURIComponent(version)}`);
    }
    
    async startSync(sources = [], force = false) {
        return this.request('POST', '/sync/start', { sources, force });
    }
    
    async getSyncStatus() {
        return this.request('GET', '/sync/status');
    }
    
    async getCacheStats() {
        return this.request('GET', '/cache/stats');
    }
    
    async clearCache(type = 'all') {
        return this.request('DELETE', '/cache/clear', { type });
    }
    
    async request(method, path, data = null) {
        const url = this.baseUrl + path;
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        if (data && ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method)) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP Error ${response.status}: ${response.statusText}`);
        }
        
        return response.json();
    }
}

// 使用示例
async function example() {
    try {
        const client = new PvmMirrorClient();
        
        // 获取状态
        const status = await client.getStatus();
        console.log('服务状态:', status.data.status);
        
        // 获取版本列表
        const versions = await client.getVersions('stable', 5);
        console.log('可用版本数:', versions.data.versions.length);
        
        // 启动同步
        const sync = await client.startSync(['php'], false);
        console.log('同步任务ID:', sync.data.task_id);
        
    } catch (error) {
        console.error('错误:', error.message);
    }
}

// 在浏览器中运行
example();
```

## 集成示例

### 1. 与 PVM 主项目集成

```bash
# 在 PVM 配置中添加镜像源
echo "export PVM_MIRROR_URL=http://localhost:34403" >> ~/.pvm/config

# 或者在配置文件中设置
cat > ~/.pvm/config/pvm-mirror.php << 'EOF'
<?php
return [
    'enabled' => true,
    'mirror_url' => 'http://localhost:34403',
    'fallback_mirrors' => [
        'https://pvm.2sxo.com',
    ],
    'timeout' => 30,
    'verify_ssl' => true,
    'speed_test_enabled' => true,
];
EOF
```

### 2. 与 CI/CD 集成

```yaml
# GitHub Actions 示例
name: PHP CI with PVM Mirror

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    services:
      pvm-mirror:
        image: ghcr.io/dongasai/php-version-manager-mirror:latest
        ports:
          - 34403:34403
        options: >-
          --health-cmd "curl -f http://localhost:34403/ping"
          --health-interval 30s
          --health-timeout 10s
          --health-retries 3
    
    steps:
    - uses: actions/checkout@v4
    
    - name: Wait for PVM Mirror
      run: |
        timeout 60 bash -c 'until curl -f http://localhost:34403/ping; do sleep 2; done'
    
    - name: Install PHP with PVM Mirror
      run: |
        export PVM_MIRROR_URL=http://localhost:34403
        # 使用 PVM 安装 PHP
        pvm install 8.3.8
```

### 3. 监控集成

```bash
# Prometheus 监控配置
cat > prometheus.yml << 'EOF'
global:
  scrape_interval: 15s

scrape_configs:
  - job_name: 'pvm-mirror'
    static_configs:
      - targets: ['localhost:34403']
    metrics_path: '/api/metrics'
    scrape_interval: 30s
EOF

# 启动 Prometheus
docker run -d \
  --name prometheus \
  -p 9090:9090 \
  -v $(pwd)/prometheus.yml:/etc/prometheus/prometheus.yml \
  prom/prometheus
```

## 故障排除示例

### 1. 常见问题诊断

```bash
# 检查服务状态
./bin/pvm-mirror status

# 检查日志
tail -f logs/server.log
tail -f logs/error.log

# 检查配置
./bin/pvm-mirror config --validate

# 测试网络连接
curl -I http://localhost:34403/ping

# 检查磁盘空间
df -h data/

# 检查内存使用
free -h
```

### 2. 性能优化

```bash
# 调整缓存大小
export PVM_MIRROR_CACHE_SIZE=536870912  # 512MB

# 增加并发连接数
export PVM_MIRROR_MAX_CONNECTIONS=200

# 调整同步间隔
export PVM_MIRROR_SYNC_INTERVAL=12  # 12小时

# 启用压缩
export PVM_MIRROR_ENABLE_COMPRESSION=true
```

### 3. 备份和恢复

```bash
# 备份数据
tar -czf pvm-mirror-backup-$(date +%Y%m%d).tar.gz \
  data/ logs/ config/

# 恢复数据
tar -xzf pvm-mirror-backup-20250630.tar.gz

# 数据库备份（如果使用）
mysqldump -u pvm_mirror -p pvm_mirror > backup.sql

# 恢复数据库
mysql -u pvm_mirror -p pvm_mirror < backup.sql
```

## 最佳实践

### 1. 生产环境部署

- 使用 HTTPS 和 SSL 证书
- 配置防火墙和安全组
- 设置监控和告警
- 定期备份数据
- 使用负载均衡器

### 2. 性能优化

- 合理设置缓存大小
- 使用 SSD 存储
- 优化网络带宽
- 监控系统资源

### 3. 安全考虑

- 限制访问IP
- 启用速率限制
- 定期更新系统
- 监控异常访问

### 4. 维护建议

- 定期清理日志
- 监控磁盘使用
- 更新镜像内容
- 检查服务健康状态
