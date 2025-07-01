# API 文档

PHP Version Manager Mirror 提供了 RESTful API 接口，供'PHP Version Manager'获取数据用


## 接口列表
1. 系统状态 ,'api/status'
2. 可用PHP版本列表,'api/php/version/{PHP大版本,例如7.1}'
3. 可用PECL扩展列表,,'api/php/pecl/{PHP大版本,例如7.1}'
3.1 PECL扩展版本列表,'api/pecl/{PECL扩展名称,例如redis}' 
4. 可用Composer版本列表, 'api/composer'
5. 可用源码扩展列表, 'api/extensions/{扩展名称,例如swoole}'


## 础信息

- **Base URL**: `http://localhost:34403/api`
- **API Version**: v1
- **Content-Type**: `application/json`

## 通用响应格式

### 成功响应

```json
{
  "success": true,
  "data": {
    // 响应数据
  },
  "message": "操作成功",
  "timestamp": "2025-06-30T14:30:00Z"
}
```

### 错误响应

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "错误描述",
    "details": "详细错误信息"
  },
  "timestamp": "2025-06-30T14:30:00Z"
}
```

## API 端点

### 1. 系统状态

#### GET /api/status

获取系统状态信息。

**请求示例:**
```bash
curl -X GET http://localhost:34403/api/status
```

**响应示例:**
```json
{
  "success": true,
  "data": {
    "status": "running",
    "version": "1.0.0",
    "uptime": 3600,
    "memory_usage": {
      "used": "128MB",
      "total": "512MB",
      "percentage": 25
    },
    "disk_usage": {
      "used": "2.5GB",
      "total": "50GB",
      "percentage": 5
    },
    "cache": {
      "enabled": true,
      "size": "45MB",
      "hit_rate": 85.5
    },
    "last_sync": "2025-06-30T12:00:00Z"
  }
}
```

### 2. 版本管理

#### GET /api/versions

获取所有可用的 PHP 版本。

**请求参数:**
- `type` (可选): 版本类型 (`stable`, `beta`, `alpha`, `all`)
- `limit` (可选): 返回数量限制，默认 50

**请求示例:**
```bash
curl -X GET "http://localhost:34403/api/versions?type=stable&limit=10"
```

**响应示例:**
```json
{
  "success": true,
  "data": {
    "versions": [
      {
        "version": "8.3.8",
        "type": "stable",
        "release_date": "2025-06-15",
        "download_url": "http://localhost:34403/downloads/php-8.3.8.tar.gz",
        "checksum": {
          "md5": "abc123...",
          "sha256": "def456..."
        },
        "size": 15728640
      }
    ],
    "total": 25,
    "page": 1,
    "per_page": 10
  }
}
```

#### GET /api/versions/{version}

获取特定版本的详细信息。

**请求示例:**
```bash
curl -X GET http://localhost:34403/api/versions/8.3.8
```

**响应示例:**
```json
{
  "success": true,
  "data": {
    "version": "8.3.8",
    "type": "stable",
    "release_date": "2025-06-15",
    "download_url": "http://localhost:34403/downloads/php-8.3.8.tar.gz",
    "mirror_url": "http://localhost:34403/mirror/php-8.3.8.tar.gz",
    "official_url": "https://www.php.net/distributions/php-8.3.8.tar.gz",
    "checksum": {
      "md5": "abc123...",
      "sha256": "def456..."
    },
    "size": 15728640,
    "available": true,
    "last_updated": "2025-06-30T10:00:00Z"
  }
}
```

### 3. 下载管理

#### GET /api/downloads

获取下载统计信息。

**请求参数:**
- `period` (可选): 统计周期 (`hour`, `day`, `week`, `month`)
- `version` (可选): 特定版本

**请求示例:**
```bash
curl -X GET "http://localhost:34403/api/downloads?period=day"
```

**响应示例:**
```json
{
  "success": true,
  "data": {
    "period": "day",
    "total_downloads": 1250,
    "unique_ips": 85,
    "top_versions": [
      {
        "version": "8.3.8",
        "downloads": 450,
        "percentage": 36
      },
      {
        "version": "8.2.20",
        "downloads": 320,
        "percentage": 25.6
      }
    ],
    "bandwidth_used": "15.2GB"
  }
}
```

#### POST /api/downloads

记录下载请求（内部使用）。

### 4. 同步管理

#### GET /api/sync/status

获取同步状态。

**请求示例:**
```bash
curl -X GET http://localhost:34403/api/sync/status
```

**响应示例:**
```json
{
  "success": true,
  "data": {
    "status": "idle",
    "last_sync": "2025-06-30T12:00:00Z",
    "next_sync": "2025-07-01T12:00:00Z",
    "progress": {
      "current": 0,
      "total": 0,
      "percentage": 0
    },
    "sources": {
      "php": {
        "enabled": true,
        "last_sync": "2025-06-30T12:00:00Z",
        "status": "success",
        "versions_synced": 25
      },
      "composer": {
        "enabled": true,
        "last_sync": "2025-06-30T06:00:00Z",
        "status": "success",
        "versions_synced": 3
      }
    }
  }
}
```

#### POST /api/sync/start

启动同步任务。

**请求参数:**
```json
{
  "sources": ["php", "composer", "pecl"],
  "force": false
}
```

**请求示例:**
```bash
curl -X POST http://localhost:34403/api/sync/start \
  -H "Content-Type: application/json" \
  -d '{"sources": ["php"], "force": true}'
```

**响应示例:**
```json
{
  "success": true,
  "data": {
    "task_id": "sync_20250630_143000",
    "status": "started",
    "estimated_duration": 1800
  }
}
```

#### POST /api/sync/stop

停止同步任务。

### 5. 缓存管理

#### GET /api/cache/stats

获取缓存统计信息。

**响应示例:**
```json
{
  "success": true,
  "data": {
    "enabled": true,
    "size": "45MB",
    "max_size": "100MB",
    "usage_percentage": 45,
    "hit_rate": 85.5,
    "miss_rate": 14.5,
    "entries": 1250,
    "oldest_entry": "2025-06-29T14:30:00Z"
  }
}
```

#### DELETE /api/cache/clear

清空缓存。

**请求参数:**
```json
{
  "type": "all"  // 或 "expired", "specific"
}
```

### 6. 配置管理

#### GET /api/config

获取当前配置（敏感信息已隐藏）。

**响应示例:**
```json
{
  "success": true,
  "data": {
    "server": {
      "host": "0.0.0.0",
      "port": 34403,
      "max_connections": 100
    },
    "cache": {
      "enabled": true,
      "max_size": 104857600,
      "ttl": 3600
    },
    "sources": {
      "php": {
        "enabled": true,
        "sync_interval": 24
      }
    }
  }
}
```

#### PUT /api/config

更新配置（需要重启服务生效）。

### 7. 监控和指标

#### GET /api/metrics

获取系统指标（Prometheus 格式）。

**响应示例:**
```
# HELP pvm_mirror_requests_total Total number of requests
# TYPE pvm_mirror_requests_total counter
pvm_mirror_requests_total{method="GET",status="200"} 1250

# HELP pvm_mirror_response_time_seconds Response time in seconds
# TYPE pvm_mirror_response_time_seconds histogram
pvm_mirror_response_time_seconds_bucket{le="0.1"} 800
pvm_mirror_response_time_seconds_bucket{le="0.5"} 1200
```

#### GET /api/health

健康检查端点。

**响应示例:**
```json
{
  "success": true,
  "data": {
    "status": "healthy",
    "checks": {
      "database": "ok",
      "disk_space": "ok",
      "memory": "ok",
      "network": "ok"
    }
  }
}
```

## 错误代码

| 代码 | 描述 |
|------|------|
| `INVALID_REQUEST` | 请求格式错误 |
| `VERSION_NOT_FOUND` | 版本不存在 |
| `SYNC_IN_PROGRESS` | 同步正在进行中 |
| `CACHE_ERROR` | 缓存操作失败 |
| `CONFIG_ERROR` | 配置错误 |
| `INTERNAL_ERROR` | 内部服务器错误 |
| `RATE_LIMITED` | 请求频率超限 |
| `SERVICE_UNAVAILABLE` | 服务不可用 |

## 使用示例

### PHP 客户端示例

```php
<?php
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
        return $this->request('GET', '/versions?' . http_build_query($params));
    }
    
    public function startSync($sources = [], $force = false)
    {
        return $this->request('POST', '/sync/start', [
            'sources' => $sources,
            'force' => $force
        ]);
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
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return json_decode($response, true);
    }
}

// 使用示例
$client = new PvmMirrorClient();
$status = $client->getStatus();
$versions = $client->getVersions('stable', 10);
```

### JavaScript 客户端示例

```javascript
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
        
        return this.request('GET', `/versions?${params}`);
    }
    
    async startSync(sources = [], force = false) {
        return this.request('POST', '/sync/start', {
            sources,
            force
        });
    }
    
    async request(method, path, data = null) {
        const url = this.baseUrl + path;
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json'
            }
        };
        
        if (data && ['POST', 'PUT', 'PATCH'].includes(method)) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(url, options);
        return response.json();
    }
}

// 使用示例
const client = new PvmMirrorClient();
client.getStatus().then(status => console.log(status));
```

## 速率限制

API 默认启用速率限制：
- **限制**: 每小时 1000 次请求
- **响应头**: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`
- **超限响应**: HTTP 429 Too Many Requests

## 版本兼容性

- **API Version**: v1
- **向后兼容**: 保证同一主版本内的向后兼容性
- **废弃通知**: 废弃的端点会提前 6 个月通知
