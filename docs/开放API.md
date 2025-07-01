# API 接口详细文档

PHP Version Manager Mirror 提供了 RESTful API 接口，供'PHP Version Manager'获取数据用

## 接口列表

### 核心API接口
1. **系统状态** - `GET /api/status`
2. **PHP版本列表** - `GET /api/php/version/{major_version}`
3. **PECL扩展列表** - `GET /api/php/pecl/{major_version}`
4. **PECL扩展版本列表** - `GET /api/pecl/{extension_name}`
5. **Composer版本列表** - `GET /api/composer`

## 基础信息

- **Base URL**: `http://localhost:34403/api`
- **API Version**: v1
- **Content-Type**: `application/json; charset=utf-8`
- **响应格式**: 统一JSON格式
- **认证方式**: 无需认证（公开API）
- **缓存策略**: 多级缓存，根据数据类型设置不同TTL

## 通用响应格式

### 成功响应

```json
{
  "success": true,
  "data": {
    // 响应数据
  },
  "message": "操作成功",
  "timestamp": "2025-07-01T15:58:10Z"
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
  "timestamp": "2025-07-01T15:58:10Z"
}
```

## 响应头规范

### 通用响应头
- `Content-Type`: `application/json; charset=utf-8`
- `X-API-Version`: `v1`
- `X-Response-Time`: 响应时间(毫秒)
- `X-Cache-Status`: 缓存状态 (`HIT`, `MISS`, `BYPASS`)

### 缓存相关响应头
- `Cache-Control`: 缓存控制策略
- `ETag`: 资源版本标识
- `Last-Modified`: 最后修改时间

### 速率限制响应头
- `X-RateLimit-Limit`: 速率限制上限
- `X-RateLimit-Remaining`: 剩余请求次数
- `X-RateLimit-Reset`: 限制重置时间

## API 端点详细说明

### 1. 系统状态接口

#### GET /api/status

获取系统运行状态和各镜像服务状态。

**缓存**: 5分钟  
**认证**: 无需认证

**请求参数**: 无

**请求示例:**
```bash
curl -X GET http://localhost:34403/api/status
```

**成功响应 (HTTP 200):**
```json
{
  "success": true,
  "data": {
    "status": "running",
    "version": "2.0.0",
    "uptime": 86400,
    "memory_usage": {
      "used": "256MB",
      "total": "1GB", 
      "percentage": 25.6
    },
    "disk_usage": {
      "used": "15.2GB",
      "total": "100GB",
      "percentage": 15.2
    },
    "cache": {
      "enabled": true,
      "size": "128MB",
      "hit_rate": 89.5,
      "entries": 2048
    },
    "mirrors": {
      "php": {
        "name": "PHP Official Mirror",
        "enabled": true,
        "file_count": 156,
        "total_size": "2.5GB",
        "last_updated": "2025-07-01T08:00:00Z",
        "sync_status": "completed"
      },
      "pecl": {
        "name": "PECL Extensions Mirror", 
        "enabled": true,
        "file_count": 89,
        "total_size": "450MB",
        "last_updated": "2025-07-01T06:30:00Z",
        "sync_status": "running"
      },
      "composer": {
        "name": "Composer Releases Mirror",
        "enabled": true, 
        "file_count": 12,
        "total_size": "85MB",
        "last_updated": "2025-07-01T07:15:00Z",
        "sync_status": "completed"
      },
      "extensions": {
        "name": "GitHub Extensions Mirror",
        "enabled": true,
        "file_count": 45,
        "total_size": "320MB", 
        "last_updated": "2025-07-01T05:45:00Z",
        "sync_status": "failed"
      }
    },
    "jobs": {
      "total": 1250,
      "running": 2,
      "completed": 1200,
      "failed": 48
    },
    "last_sync": "2025-07-01T08:00:00Z"
  },
  "message": "系统运行正常",
  "timestamp": "2025-07-01T15:58:10Z"
}
```

**错误响应 (HTTP 500):**
```json
{
  "success": false,
  "error": {
    "code": "INTERNAL_ERROR",
    "message": "系统内部错误",
    "details": "无法获取系统状态信息"
  },
  "timestamp": "2025-07-01T15:58:10Z"
}
```

### 2. PHP版本列表接口

#### GET /api/php/version/{major_version}

获取指定PHP大版本的所有可用版本。

**缓存**: 1小时  
**认证**: 无需认证

**路径参数:**
- `major_version` (string, required): PHP大版本号，如 "7.4", "8.0", "8.1", "8.2", "8.3"

**查询参数:**
- `type` (string, optional): 版本类型过滤
  - 可选值: `stable`, `beta`, `alpha`, `rc`, `all`
  - 默认值: `stable`
- `limit` (integer, optional): 返回数量限制
  - 范围: 1-100
  - 默认值: 50
- `sort` (string, optional): 排序方式
  - 可选值: `version_asc`, `version_desc`, `date_asc`, `date_desc`
  - 默认值: `version_desc`

**请求示例:**
```bash
curl -X GET "http://localhost:34403/api/php/version/8.3?type=stable&limit=10"
```

**成功响应 (HTTP 200):**
```json
{
  "success": true,
  "data": {
    "major_version": "8.3",
    "versions": [
      {
        "version": "8.3.8",
        "type": "stable",
        "release_date": "2025-06-15",
        "download_url": "http://localhost:34403/downloads/php-8.3.8.tar.gz",
        "mirror_url": "http://localhost:34403/mirror/php/8.3/php-8.3.8.tar.gz",
        "official_url": "https://www.php.net/distributions/php-8.3.8.tar.gz",
        "checksum": {
          "md5": "a1b2c3d4e5f6789012345678901234567",
          "sha256": "abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890"
        },
        "size": 15728640,
        "available": true,
        "last_updated": "2025-06-15T10:30:00Z"
      }
    ],
    "total": 25,
    "filtered": 1,
    "page": 1,
    "per_page": 50,
    "has_more": false
  },
  "message": "获取成功",
  "timestamp": "2025-07-01T15:58:10Z"
}
```

**错误响应 (HTTP 404):**
```json
{
  "success": false,
  "error": {
    "code": "VERSION_NOT_FOUND",
    "message": "指定的PHP版本不存在",
    "details": "PHP版本 '9.0' 不在支持的版本列表中"
  },
  "timestamp": "2025-07-01T15:58:10Z"
}
```
