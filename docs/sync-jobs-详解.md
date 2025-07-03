# 同步任务管理页面详解

## 概述

`/admin/sync-jobs` 页面是PHP版本管理器镜像站的核心管理界面，用于监控和管理各种镜像的同步任务。本文档详细说明页面的数据来源、计算方式和业务逻辑。

## 数据来源

### 1. 数据库表结构

同步任务数据存储在 `sync_jobs` 表中：

```sql
CREATE TABLE sync_jobs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    mirror_type VARCHAR(50) NOT NULL COMMENT '镜像类型',
    status VARCHAR(20) DEFAULT 'pending' COMMENT '状态',
    progress INTEGER DEFAULT 0 COMMENT '进度百分比',
    log TEXT COMMENT '日志信息',
    started_at TIMESTAMP NULL COMMENT '开始时间',
    completed_at TIMESTAMP NULL COMMENT '完成时间',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 2. 镜像类型配置

镜像类型基于硬编码配置，定义在 `config/mirror.php` 中：

```php
return [
    'php' => [
        'enabled' => true,
        'name' => 'PHP源码',
        'description' => 'PHP官方源码镜像'
    ],
    'pecl' => [
        'enabled' => true,
        'name' => 'PECL扩展',
        'description' => 'PHP扩展库镜像'
    ],
    'github' => [
        'enabled' => true,
        'name' => 'GitHub扩展',
        'description' => 'GitHub上的PHP扩展'
    ],
    'composer' => [
        'enabled' => true,
        'name' => 'Composer包',
        'description' => 'Composer包管理器镜像'
    ]
];
```

## 字段详解

### 1. ID
- **数据来源**: 数据库自增主键
- **用途**: 唯一标识每个同步任务
- **显示**: 直接显示数字ID

### 2. 镜像类型 (mirror_type)
- **数据来源**: 数据库 `mirror_type` 字段
- **可选值**: `php`, `pecl`, `github`, `composer`
- **显示转换**: 通过映射数组转换为中文显示

```php
$types = [
    'php' => 'PHP源码',
    'pecl' => 'PECL扩展', 
    'github' => 'GitHub扩展',
    'composer' => 'Composer包',
];
```

### 3. 状态 (status)
- **数据来源**: 数据库 `status` 字段
- **可选值**: 
  - `pending` - 等待中
  - `running` - 运行中
  - `completed` - 已完成
  - `failed` - 失败
  - `cancelled` - 已取消

### 4. 进度 (progress)
- **数据来源**: 数据库 `progress` 字段
- **数据类型**: INTEGER (0-100)
- **显示方式**: 进度条 + 百分比文字

## 进度百分比计算详解

### 1. 进度值的含义

进度百分比表示同步任务的完成程度，范围为 0-100：
- **0%**: 任务刚创建，尚未开始
- **1-99%**: 任务正在执行中
- **100%**: 任务已完成

### 2. 进度计算方式

进度的计算取决于具体的镜像类型和同步策略：

#### PHP源码镜像
```php
// 示例计算逻辑
$totalVersions = count($phpVersionsToSync);  // 需要同步的PHP版本总数
$syncedVersions = count($completedVersions); // 已完成同步的版本数
$progress = ($syncedVersions / $totalVersions) * 100;
```

#### PECL扩展镜像
```php
// 示例计算逻辑
$totalExtensions = count($peclExtensionsToSync);  // 需要同步的扩展总数
$syncedExtensions = count($completedExtensions); // 已完成同步的扩展数
$progress = ($syncedExtensions / $totalExtensions) * 100;
```

#### Composer包镜像
```php
// 示例计算逻辑
$totalPackages = count($packagesToSync);    // 需要同步的包总数
$syncedPackages = count($completedPackages); // 已完成同步的包数
$progress = ($syncedPackages / $totalPackages) * 100;
```

### 3. 进度更新机制

进度在同步过程中实时更新，通过 SyncJob 模型的 `updateProgress` 方法：

```php
// SyncJob 模型中的进度更新方法
public function updateProgress(int $progress): bool
{
    return $this->update(['progress' => max(0, min(100, $progress))]);
}
```

#### 实际的同步进度计算

**GitHub扩展同步**（来自 `SyncService::syncGithubMirror`）：
```php
foreach ($extensions as $index => $extensionName) {
    $current = $index + 1;
    $this->updateJobLog($syncJob, "同步GitHub扩展: {$extensionName} ({$current}/{$totalExtensions})");

    if ($this->syncGithubExtension($syncJob, $extensionName, $dataDir)) {
        $successCount++;
    }

    // 更新进度：当前完成数 / 总数 * 100
    $progress = (int)(($index + 1) / $totalExtensions * 100);
    $syncJob->updateProgress($progress);
}
```

**Composer包同步**（来自 `SyncService::syncComposerMirror`）：
```php
foreach ($versions as $index => $version) {
    $current = $index + 1;
    $this->updateJobLog($syncJob, "同步Composer版本: {$version} ({$current}/{$totalVersions})");

    if ($this->downloadComposerVersion($syncJob, $version, $dataDir, $config)) {
        $successCount++;
    }

    // 更新进度：当前完成数 / 总数 * 100
    $progress = (int)(($index + 1) / $totalVersions * 100);
    $syncJob->updateProgress($progress);
}
```

**扩展镜像同步**（来自 `ExtensionMirrorService`）：
```php
foreach ($extensions as $index => $extension) {
    $current = $index + 1;
    $this->updateJobLog($syncJob, "同步GitHub扩展: {$extension['name']} ({$current}/{$totalExtensions})");

    if ($this->syncSingleExtension($syncJob, $extension, $baseDir)) {
        $successCount++;
    }

    // 更新进度：当前完成数 / 总数 * 100
    $progress = (int)(($index + 1) / $totalExtensions * 100);
    $syncJob->updateProgress($progress);
}
```

## 时间字段详解

### 1. 创建时间 (created_at)
- **设置时机**: 任务创建时自动设置
- **用途**: 记录任务创建的时间点
- **格式**: `Y-m-d H:i:s` (如: 2025-07-02 14:33:07)

### 2. 开始时间 (started_at)
- **设置时机**: 任务状态变为 `running` 时设置
- **用途**: 记录任务实际开始执行的时间
- **可能为空**: 等待中的任务此字段为 NULL

### 3. 完成时间 (completed_at)
- **设置时机**: 任务状态变为 `completed` 或 `failed` 时设置
- **用途**: 记录任务结束的时间
- **可能为空**: 未完成的任务此字段为 NULL

## 日志信息 (log)

### 1. 日志格式
```
[2025-07-02 14:33:07] 开始同步PHP源码镜像
[2025-07-02 14:33:08] 正在下载 PHP 8.3.0 源码包...
[2025-07-02 14:33:15] PHP 8.3.0 下载完成
[2025-07-02 14:33:16] 正在下载 PHP 8.2.15 源码包...
[2025-07-02 14:33:23] PHP 8.2.15 下载完成
[2025-07-02 14:33:24] 同步完成，共处理 2 个版本
```

### 2. 日志记录方式
```php
// 在 SyncJob 模型中添加日志
public function addLog(string $message): bool
{
    $timestamp = now()->format('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}";
    
    return $this->update([
        'log' => $this->log . "\n" . $logEntry
    ]);
}
```

## 页面展示逻辑

### 1. 列表页面 (`/admin/sync-jobs`)

**控制器**: `App\Admin\Controllers\SyncJobController@grid`

**数据查询**:
```php
return Grid::make(new SyncJob(), function (Grid $grid) {
    $grid->column('id')->sortable();
    $grid->column('mirror_type', '镜像类型')->display(function ($value) {
        $types = [
            'php' => 'PHP源码',
            'pecl' => 'PECL扩展',
            'github' => 'GitHub扩展',
            'composer' => 'Composer包',
        ];
        return $types[$value] ?? $value;
    });
    // ... 其他列配置
});
```

### 2. 详情页面 (`/admin/sync-jobs/{id}`)

**控制器**: `App\Admin\Controllers\SyncJobController@detail`

**特殊处理**:
- 日志信息使用 `<pre>` 标签格式化显示
- 时间字段处理 NULL 值和字符串格式
- 进度显示为百分比格式

## 业务流程

### 1. 任务创建流程

**步骤1: 创建同步任务**（`MirrorService::syncMirrorByType`）
```php
// 创建同步任务
$syncJob = SyncJob::create([
    'mirror_type' => $type,
    'status' => 'pending',
    'progress' => 0,
    'log' => '',
]);

// 分发同步任务到队列
SyncMirrorJob::dispatch($syncJob);
```

**步骤2: 队列处理任务**（`SyncService::executeSyncJob`）
```php
// 更新任务状态为运行中
$syncJob->update([
    'status' => 'running',
    'started_at' => now(),
    'progress' => 0,
]);

// 根据镜像类型执行不同的同步逻辑
$result = match ($mirrorType) {
    'php' => $this->syncPhpMirror($syncJob),
    'pecl' => $this->syncPeclMirror($syncJob),
    'github' => $this->syncGithubMirror($syncJob),
    'composer' => $this->syncComposerMirror($syncJob),
    default => throw new \Exception("不支持的镜像类型: {$mirrorType}")
};
```

**步骤3: 完成任务**
```php
if ($result) {
    $syncJob->update([
        'status' => 'completed',
        'progress' => 100,
        'completed_at' => now(),
    ]);
}
```

### 2. 错误处理流程
```php
try {
    // 执行同步逻辑
} catch (\Exception $e) {
    $syncJob->update([
        'status' => 'failed',
        'log' => $syncJob->log . "\n错误: " . $e->getMessage(),
    ]);

    Log::error("镜像同步失败", [
        'job_id' => $syncJob->id,
        'error' => $e->getMessage()
    ]);
}
```

## 监控和维护

### 1. 性能监控
- 监控长时间运行的任务（超过预期时间）
- 监控失败率较高的镜像类型
- 监控磁盘空间使用情况

### 2. 数据清理
```php
// 清理过期任务记录
SyncJob::cleanupExpiredJobs(30); // 清理30天前的已完成任务
```

### 3. 状态检查
```php
// 检查异常状态的任务
$stuckJobs = SyncJob::where('status', 'running')
    ->where('updated_at', '<', now()->subHours(2))
    ->get();
```

## 进度计算示例

### 实际运行示例

假设同步 GitHub 扩展，需要处理以下扩展：
1. `swoole/swoole-src`
2. `redis/phpredis`
3. `mongodb/mongo-php-driver`
4. `xdebug/xdebug`
5. `imagick/imagick`

**进度计算过程**：
```
总扩展数: 5
当前处理: swoole/swoole-src (1/5)
进度计算: (1/5) * 100 = 20%

当前处理: redis/phpredis (2/5)
进度计算: (2/5) * 100 = 40%

当前处理: mongodb/mongo-php-driver (3/5)
进度计算: (3/5) * 100 = 60%

当前处理: xdebug/xdebug (4/5)
进度计算: (4/5) * 100 = 80%

当前处理: imagick/imagick (5/5)
进度计算: (5/5) * 100 = 100%
```

### 日志记录示例

```
[2025-07-02 14:33:07] 开始同步GitHub扩展...
[2025-07-02 14:33:08] 发现 5 个GitHub扩展需要同步
[2025-07-02 14:33:09] 同步GitHub扩展: swoole/swoole-src (1/5)
[2025-07-02 14:33:25] 同步GitHub扩展: redis/phpredis (2/5)
[2025-07-02 14:33:41] 同步GitHub扩展: mongodb/mongo-php-driver (3/5)
[2025-07-02 14:33:58] 同步GitHub扩展: xdebug/xdebug (4/5)
[2025-07-02 14:34:12] 同步GitHub扩展: imagick/imagick (5/5)
[2025-07-02 14:34:28] GitHub扩展同步完成，成功同步 5/5 个扩展
```

## 总结

同步任务管理页面通过数据库记录和实时更新机制，提供了完整的任务监控和管理功能。进度百分比基于实际同步项目的完成情况计算，采用简单的线性计算方式：

**进度 = (已完成项目数 / 总项目数) × 100**

这种计算方式简单直观，为运维人员提供了清晰的任务执行状态信息，便于监控同步进度和排查问题。
