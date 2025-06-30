# pvm-mirror 的Docker

这个目录包含了pvm-mirror的Docker容器化配置文件。

## 文件说明

### Dockerfile
pvm-mirror的容器镜像定义文件，基于Ubuntu 22.04构建，包含：
- PHP 8.1运行环境
- 必要的系统依赖和PHP扩展
- pvm-mirror专用用户
- 健康检查配置
- 默认启动命令

### dev-compose.yml
开发环境的Docker Compose配置文件，特性：
- 映射项目的data目录到容器的数据目录
- 映射项目的logs目录到容器的日志目录
- 映射源代码目录用于开发调试
- 容器自动重启
- 启动后即运行pvm-mirror服务
- 开放默认端口34403
- 设置开发环境变量
- 支持.env文件配置
- 启用调试模式和详细日志

### prod-compose.yml
生产环境的Docker Compose配置文件，特性：
- 使用Docker卷持久化数据
- 优化的资源限制和健康检查
- 生产环境优化的配置
- 更大的缓存和连接限制
- 只读配置文件映射

### entrypoint.sh
容器启动脚本，功能：
- 环境变量验证和处理
- 目录创建和权限检查
- 开发/生产环境自动配置
- 依赖检查和错误处理
- 支持.env文件加载

## 使用方法

### 构建镜像
```bash
# 在项目根目录执行
docker build -f docker/pvm-mirror/Dockerfile -t pvm-mirror .
```

### 启动开发环境
```bash
# 进入pvm-mirror目录
cd docker/pvm-mirror

# 复制环境变量模板（可选）
cp .env.example .env

# 编辑环境变量（可选）
nano .env

# 启动开发环境
docker compose -f dev-compose.yml up -d

# 查看日志
docker compose -f dev-compose.yml logs -f

# 停止服务
docker compose -f dev-compose.yml down
```

### 启动生产环境
```bash
# 进入pvm-mirror目录
cd docker/pvm-mirror

# 配置生产环境变量
cp .env.example .env
nano .env  # 设置生产环境配置

# 启动生产环境
docker compose -f prod-compose.yml up -d

# 查看状态
docker compose -f prod-compose.yml ps

# 查看日志
docker compose -f prod-compose.yml logs -f

# 停止服务
docker compose -f prod-compose.yml down
```

### 访问服务
- Web界面：http://localhost:34403
- 容器内部：`docker exec -it pvm-mirror-dev bash`

## 环境变量

pvm-mirror支持通过环境变量进行配置，这些变量会覆盖配置文件中的设置。

### 基础配置

| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `PVM_MIRROR_ENV` | `production` | 运行环境 (development/production/testing) |
| `PVM_MIRROR_DEBUG` | `false` | 调试模式 (true/false) |

### 目录配置

| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `PVM_MIRROR_DATA_DIR` | `/app/data` | 数据目录路径 |
| `PVM_MIRROR_LOG_DIR` | `/app/logs` | 日志目录路径 |
| `PVM_MIRROR_CACHE_DIR` | `/app/cache` | 缓存目录路径 |

### 日志配置

| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `PVM_MIRROR_LOG_LEVEL` | `info` | 日志级别 (debug/info/warning/error) |

### 服务器配置

| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `PVM_MIRROR_HOST` | `0.0.0.0` | 监听主机 |
| `PVM_MIRROR_PORT` | `34403` | 监听端口 |
| `PVM_MIRROR_PUBLIC_URL` | `http://localhost:34403` | 公开URL |
| `PVM_MIRROR_MAX_CONNECTIONS` | `100` | 最大并发连接数 |
| `PVM_MIRROR_TIMEOUT` | `30` | 请求超时时间（秒） |

### HTTPS配置

| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `PVM_MIRROR_ENABLE_HTTPS` | `false` | 是否启用HTTPS |
| `PVM_MIRROR_SSL_CERT` | - | SSL证书路径 |
| `PVM_MIRROR_SSL_KEY` | - | SSL密钥路径 |

### 缓存配置

| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `PVM_MIRROR_CACHE_SIZE` | `104857600` | 缓存最大大小（字节） |
| `PVM_MIRROR_CACHE_TTL` | `3600` | 缓存TTL（秒） |

### 同步配置

| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `PVM_MIRROR_SYNC_INTERVAL` | `24` | 同步间隔（小时） |
| `PVM_MIRROR_MAX_RETRIES` | `3` | 最大重试次数 |
| `PVM_MIRROR_RETRY_INTERVAL` | `300` | 重试间隔（秒） |

### 使用.env文件

1. 复制环境变量模板：
```bash
cp .env.example .env
```

2. 编辑.env文件：
```bash
# 设置为开发环境
PVM_MIRROR_ENV=development
PVM_MIRROR_DEBUG=true
PVM_MIRROR_LOG_LEVEL=debug

# 自定义端口
PVM_MIRROR_PORT=8080
PVM_MIRROR_PUBLIC_URL=http://localhost:8080
```

3. 启动容器（会自动加载.env文件）：
```bash
docker compose -f dev-compose.yml up -d
```

## 数据持久化

开发环境通过卷映射实现数据持久化：
- `../../data:/app/data` - 镜像数据
- `../../logs:/app/logs` - 日志文件
- `../../configMirror:/app/configMirror` - 配置文件
- `../../cache:/app/cache` - 缓存文件

## 健康检查

容器包含健康检查配置，每30秒检查一次服务状态：
```bash
# 手动检查健康状态
docker inspect pvm-mirror-dev | grep Health -A 10
```

## 故障排除

### 查看容器日志
```bash
docker logs pvm-mirror-dev
```

### 进入容器调试
```bash
docker exec -it pvm-mirror-dev bash
```

### 重启服务
```bash
docker-compose -f dev-compose.yml restart
```