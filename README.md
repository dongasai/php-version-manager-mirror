# PHP Version Manager Mirror

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.1%2B-blue.svg)](https://php.net)
[![Docker](https://img.shields.io/badge/Docker-Ready-blue.svg)](https://docker.com)

PHP Version Manager Mirror Service - 独立的PHP版本管理镜像服务，为PVM项目提供高速、稳定的下载镜像源。

## 🚀 功能特性

- **多源镜像**: 支持PHP源码、PECL扩展、Composer等多种资源镜像
- **智能同步**: 自动从官方源同步最新版本，支持增量更新
- **高性能**: 内置缓存机制，支持并发下载和断点续传
- **Docker支持**: 提供完整的Docker容器化解决方案
- **Web界面**: 直观的Web管理界面，支持状态监控和配置管理
- **API接口**: RESTful API接口，支持程序化访问和集成
- **多架构**: 支持AMD64和ARM64架构

## 📦 快速开始

### 使用Docker（推荐）

```bash
# 克隆项目
git clone https://github.com/pvm-project/php-version-manager-mirror.git
cd php-version-manager-mirror

# 启动开发环境
cd docker
docker compose -f dev-compose.yml up -d

# 访问Web界面
open http://localhost:34403
```

### 本地安装

```bash
# 克隆项目
git clone https://github.com/pvm-project/php-version-manager-mirror.git
cd php-version-manager-mirror

# 安装依赖（可选）
composer install

# 启动镜像服务
./bin/pvm-mirror server start

# 访问Web界面
open http://localhost:34403
```

## 🛠️ 命令行工具

```bash
# 查看状态
./bin/pvm-mirror status

# 同步镜像内容
./bin/pvm-mirror sync

# 启动服务器
./bin/pvm-mirror server start

# 停止服务器
./bin/pvm-mirror server stop

# 查看帮助
./bin/pvm-mirror help
```

## 🐳 Docker部署

### 开发环境

```bash
cd docker
docker compose -f dev-compose.yml up -d
```

### 生产环境

```bash
cd docker
cp .env.example .env
# 编辑.env文件设置生产环境配置
docker compose -f prod-compose.yml up -d
```

## 📖 配置说明

主要配置文件位于 `config/` 目录：

- `runtime.php` - 运行时配置（服务器、缓存、日志等）
- `mirror.php` - 镜像内容配置（同步源、版本等）
- `extensions/` - 扩展配置目录

### 环境变量配置

| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `PVM_MIRROR_PORT` | `34403` | 服务端口 |
| `PVM_MIRROR_HOST` | `0.0.0.0` | 监听地址 |
| `PVM_MIRROR_DATA_DIR` | `./data` | 数据目录 |
| `PVM_MIRROR_LOG_DIR` | `./logs` | 日志目录 |
| `PVM_MIRROR_CACHE_DIR` | `./cache` | 缓存目录 |

## 🔧 开发指南

### 环境要求

- PHP 7.1+
- Composer（可选）
- Docker（可选）

### 开发环境搭建

```bash
# 克隆项目
git clone https://github.com/pvm-project/php-version-manager-mirror.git
cd php-version-manager-mirror

# 安装开发依赖
composer install --dev

# 运行测试
composer test

# 代码风格检查
composer cs-check

# 静态分析
composer phpstan
```

### 项目结构

```
php-version-manager-mirror/
├── src/                    # 核心代码
├── config/                 # 配置文件
├── docker/                 # Docker配置
├── bin/                    # 可执行文件
├── docs/                   # 项目文档
├── tests/                  # 测试文件
├── data/                   # 数据目录
├── logs/                   # 日志目录
├── cache/                  # 缓存目录
└── public/                 # Web界面
```

## 📚 API文档

详细的API文档请参考：[API Documentation](docs/api.md)

## 🤝 贡献指南

欢迎贡献代码！请参考：[Contributing Guide](docs/contributing.md)

## 📄 许可证

本项目采用 MIT 许可证。详情请参考 [LICENSE](LICENSE) 文件。

## 🔗 相关链接

- [PVM主项目](https://github.com/pvm-project/pvm)
- [问题反馈](https://github.com/pvm-project/php-version-manager-mirror/issues)
- [更新日志](CHANGELOG.md)

## 💬 支持

如果您在使用过程中遇到问题，可以通过以下方式获取帮助：

- [GitHub Issues](https://github.com/pvm-project/php-version-manager-mirror/issues)
- [讨论区](https://github.com/pvm-project/php-version-manager-mirror/discussions)
- 邮件：support@pvm-project.org
