# Docker 部署说明

## 镜像构建

本项目使用 GitHub Actions 自动构建 Docker 镜像并推送到 GitHub Container Registry (GHCR)。

### 自动构建触发条件

- 推送到 `main` 或 `master` 分支
- 创建新的标签 (如 `v1.0.0`)
- 创建 Pull Request

### 镜像地址

```
ghcr.io/dongasai/php-version-manager-mirror:latest
```

## 部署方式

### 1. 开发环境

```bash
# 使用开发环境配置
docker compose -f docker-compose.dev.yml up -d

# 访问应用
http://localhost:8080
```

### 2. 生产环境

```bash
# 使用生产环境配置
docker compose -f docker-compose.prod.yml up -d

# 访问应用
http://localhost
```

### 3. 标准环境

```bash
# 使用标准配置 (包含 MySQL 和 Redis)
docker compose up -d

# 访问应用
http://localhost:8080
```

## 环境变量

### 必需环境变量

```bash
APP_KEY=base64:your-app-key-here
```

### 可选环境变量

```bash
# 数据库配置
MYSQL_ROOT_PASSWORD=your-root-password
MYSQL_PASSWORD=your-password

# 应用配置
APP_ENV=production
APP_DEBUG=false
```

## 数据持久化

### 重要目录挂载

- `./storage:/var/www/html/storage` - Laravel 存储目录
- `./database:/var/www/html/database` - SQLite 数据库文件
- `mirror_data:/var/www/html/data` - 镜像数据目录

### 数据库数据

- `mysql_data:/var/lib/mysql` - MySQL 数据
- `redis_data:/data` - Redis 数据

## 健康检查

所有服务都配置了健康检查：

- **应用**: HTTP 检查 `http://localhost/`
- **Redis**: `redis-cli ping`
- **MySQL**: `mysqladmin ping`

## 镜像更新

当 GitHub 仓库有新的提交时，镜像会自动构建。要使用最新镜像：

```bash
# 拉取最新镜像
docker compose pull

# 重启服务
docker compose up -d
```

## 故障排除

### 查看日志

```bash
# 查看应用日志
docker compose logs app

# 查看所有服务日志
docker compose logs
```

### 进入容器

```bash
# 进入应用容器
docker compose exec app bash

# 运行 Laravel 命令
docker compose exec app php artisan migrate
```
