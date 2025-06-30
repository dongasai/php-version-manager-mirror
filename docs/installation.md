# 安装指南

本文档详细介绍了 PHP Version Manager Mirror 的各种安装方式。

## 系统要求

### 最低要求
- **PHP**: 7.1 或更高版本
- **内存**: 512MB RAM
- **磁盘空间**: 10GB（用于镜像内容存储）
- **网络**: 稳定的互联网连接

### 推荐配置
- **PHP**: 8.1 或更高版本
- **内存**: 2GB RAM 或更多
- **磁盘空间**: 50GB 或更多
- **CPU**: 2核心或更多

### PHP扩展要求
- `curl` - 用于HTTP请求
- `json` - 用于JSON数据处理
- `mbstring` - 用于多字节字符串处理
- `zip` - 用于压缩文件处理（可选）

## 安装方式

### 方式一：Docker 安装（推荐）

Docker 是最简单、最可靠的安装方式。

#### 1. 安装 Docker

**Ubuntu/Debian:**
```bash
sudo apt update
sudo apt install docker.io docker-compose
sudo systemctl start docker
sudo systemctl enable docker
```

**CentOS/RHEL:**
```bash
sudo yum install docker docker-compose
sudo systemctl start docker
sudo systemctl enable docker
```

**macOS:**
```bash
brew install docker docker-compose
```

#### 2. 拉取镜像

```bash
# 拉取最新版本
docker pull ghcr.io/dongasai/php-version-manager-mirror:latest

# 或拉取指定版本
docker pull ghcr.io/dongasai/php-version-manager-mirror:v1.0.0
```

#### 3. 运行容器

**快速启动:**
```bash
docker run -d \
  --name pvm-mirror \
  -p 34403:34403 \
  -v pvm_mirror_data:/app/data \
  -v pvm_mirror_logs:/app/logs \
  ghcr.io/dongasai/php-version-manager-mirror:latest
```

**使用 Docker Compose:**
```bash
# 下载配置文件
curl -O https://raw.githubusercontent.com/dongasai/php-version-manager-mirror/main/docker/compose.yml

# 启动服务
docker-compose up -d
```

#### 4. 验证安装

```bash
# 检查容器状态
docker ps | grep pvm-mirror

# 测试服务
curl http://localhost:34403/ping
```

### 方式二：源码安装

适合需要自定义配置或开发环境的用户。

#### 1. 下载源码

**使用 Git:**
```bash
git clone https://github.com/dongasai/php-version-manager-mirror.git
cd php-version-manager-mirror
```

**下载发布包:**
```bash
wget https://github.com/dongasai/php-version-manager-mirror/releases/latest/download/php-version-manager-mirror.tar.gz
tar -xzf php-version-manager-mirror.tar.gz
cd php-version-manager-mirror
```

#### 2. 安装依赖（可选）

```bash
# 如果有 Composer
composer install --no-dev --optimize-autoloader

# 如果没有 Composer，使用内置自动加载器（无需额外操作）
```

#### 3. 配置权限

```bash
# 设置执行权限
chmod +x bin/pvm-mirror

# 创建数据目录
mkdir -p data logs cache
chmod 755 data logs cache
```

#### 4. 启动服务

```bash
# 启动服务器
./bin/pvm-mirror server start

# 或在后台运行
nohup ./bin/pvm-mirror server start > logs/server.log 2>&1 &
```

### 方式三：系统服务安装

将 PVM Mirror 安装为系统服务，适合生产环境。

#### 1. 创建用户

```bash
sudo useradd -r -s /bin/false pvm-mirror
sudo mkdir -p /opt/pvm-mirror
sudo chown pvm-mirror:pvm-mirror /opt/pvm-mirror
```

#### 2. 安装文件

```bash
# 下载并解压到系统目录
sudo tar -xzf php-version-manager-mirror.tar.gz -C /opt/
sudo chown -R pvm-mirror:pvm-mirror /opt/php-version-manager-mirror
```

#### 3. 创建 systemd 服务

```bash
sudo tee /etc/systemd/system/pvm-mirror.service > /dev/null <<EOF
[Unit]
Description=PHP Version Manager Mirror Service
After=network.target

[Service]
Type=forking
User=pvm-mirror
Group=pvm-mirror
WorkingDirectory=/opt/php-version-manager-mirror
ExecStart=/opt/php-version-manager-mirror/bin/pvm-mirror server start
ExecStop=/opt/php-version-manager-mirror/bin/pvm-mirror server stop
ExecReload=/opt/php-version-manager-mirror/bin/pvm-mirror server restart
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF
```

#### 4. 启动服务

```bash
sudo systemctl daemon-reload
sudo systemctl enable pvm-mirror
sudo systemctl start pvm-mirror
sudo systemctl status pvm-mirror
```

## 配置

### 环境变量配置

创建 `.env` 文件或设置环境变量：

```bash
# 基础配置
export PVM_MIRROR_HOST=0.0.0.0
export PVM_MIRROR_PORT=34403
export PVM_MIRROR_DATA_DIR=/app/data
export PVM_MIRROR_LOG_DIR=/app/logs
export PVM_MIRROR_CACHE_DIR=/app/cache

# 日志级别
export PVM_MIRROR_LOG_LEVEL=info

# 缓存配置
export PVM_MIRROR_CACHE_SIZE=104857600  # 100MB
export PVM_MIRROR_CACHE_TTL=3600        # 1小时
```

### 配置文件

主要配置文件位于 `config/` 目录：

- `runtime.php` - 运行时配置
- `mirror.php` - 镜像内容配置
- `extensions/` - 扩展配置目录

详细配置说明请参考 [配置指南](configuration.md)。

## 验证安装

### 基本功能测试

```bash
# 检查服务状态
./bin/pvm-mirror status

# 测试配置
./bin/pvm-mirror config

# 查看帮助
./bin/pvm-mirror help
```

### Web 界面测试

打开浏览器访问：`http://localhost:34403`

应该能看到 PVM Mirror 的管理界面。

### API 测试

```bash
# 测试 API 端点
curl http://localhost:34403/api/status
curl http://localhost:34403/api/versions
```

## 故障排除

### 常见问题

**1. 端口被占用**
```bash
# 检查端口占用
netstat -tlnp | grep 34403

# 修改端口配置
export PVM_MIRROR_PORT=34404
```

**2. 权限问题**
```bash
# 检查文件权限
ls -la bin/pvm-mirror
chmod +x bin/pvm-mirror

# 检查目录权限
chmod 755 data logs cache
```

**3. PHP 版本不兼容**
```bash
# 检查 PHP 版本
php -v

# 检查必需扩展
php -m | grep -E "(curl|json|mbstring)"
```

**4. 内存不足**
```bash
# 检查内存使用
free -h

# 调整 PHP 内存限制
export PHP_MEMORY_LIMIT=512M
```

### 日志查看

```bash
# 查看服务日志
tail -f logs/server.log

# 查看错误日志
tail -f logs/error.log

# Docker 日志
docker logs pvm-mirror
```

## 升级

### Docker 升级

```bash
# 拉取新版本
docker pull ghcr.io/dongasai/php-version-manager-mirror:latest

# 停止旧容器
docker stop pvm-mirror
docker rm pvm-mirror

# 启动新容器
docker run -d \
  --name pvm-mirror \
  -p 34403:34403 \
  -v pvm_mirror_data:/app/data \
  -v pvm_mirror_logs:/app/logs \
  ghcr.io/dongasai/php-version-manager-mirror:latest
```

### 源码升级

```bash
# 备份配置
cp -r config config.backup

# 下载新版本
git pull origin main
# 或下载新的发布包

# 恢复配置
cp -r config.backup/* config/

# 重启服务
./bin/pvm-mirror server restart
```

## 卸载

### Docker 卸载

```bash
# 停止并删除容器
docker stop pvm-mirror
docker rm pvm-mirror

# 删除镜像
docker rmi ghcr.io/dongasai/php-version-manager-mirror

# 删除数据卷（可选）
docker volume rm pvm_mirror_data pvm_mirror_logs
```

### 源码卸载

```bash
# 停止服务
./bin/pvm-mirror server stop

# 删除系统服务（如果安装了）
sudo systemctl stop pvm-mirror
sudo systemctl disable pvm-mirror
sudo rm /etc/systemd/system/pvm-mirror.service

# 删除文件
rm -rf /opt/php-version-manager-mirror
sudo userdel pvm-mirror
```
