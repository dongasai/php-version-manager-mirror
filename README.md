# PHP Version Manager Mirror

[![Build Status](https://github.com/dongasai/php-version-manager-mirror/workflows/Build%20and%20Push%20Docker%20Image/badge.svg)](https://github.com/dongasai/php-version-manager-mirror/actions)
[![Docker Image](https://img.shields.io/badge/docker-ghcr.io-blue)](https://github.com/dongasai/php-version-manager-mirror/pkgs/container/php-version-manager-mirror)
[![License](https://img.shields.io/github/license/dongasai/php-version-manager-mirror)](LICENSE)

基于 Laravel 10 + Dcat Admin 2.0 重构的 PHP 版本管理器镜像服务。

## 项目简介

PHP Version Manager Mirror 是一个专为 PHP 开发者设计的镜像服务，提供：

- **PHP 源码镜像** - 提供各版本 PHP 源码下载
- **PECL 扩展镜像** - 提供 PHP 扩展包下载
- **Composer 镜像** - 提供 Composer 包管理器下载
- **Web 管理界面** - 基于 Dcat Admin 的现代化管理后台
- **RESTful API** - 完整的 API 接口支持
- **容器化部署** - 基于 Docker 的一键部署

## 技术栈

- **后端框架**: Laravel 10.x
- **管理后台**: Dcat Admin 2.0
- **数据库**: SQLite / MySQL 8.0+
- **缓存**: Redis (可选)
- **容器**: Docker + php-apache
- **CI/CD**: GitHub Actions

## 快速开始

### 使用 Docker 部署

```bash
# 克隆项目
git clone https://github.com/dongasai/php-version-manager-mirror.git
cd php-version-manager-mirror

# 开发环境
docker compose -f docker-compose.dev.yml up -d

# 生产环境
docker compose -f docker-compose.prod.yml up -d
```

### 本地开发

```bash
# 安装依赖
composer install

# 配置环境
cp .env.example .env
php artisan key:generate

# 数据库迁移
php artisan migrate

# 安装 Dcat Admin
php artisan admin:install

# 启动服务
php artisan serve
```

## 访问地址

- **前端界面**: http://localhost:8080
- **管理后台**: http://localhost:8080/admin
- **API 文档**: http://localhost:8080/docs

## 主要功能

### 镜像服务
- PHP 源码版本管理和下载
- PECL 扩展包管理和下载
- Composer 版本管理和下载
- 自动同步和缓存机制

### 管理功能
- 系统状态监控
- 镜像配置管理
- 同步任务管理
- 访问日志查看
- 系统配置管理

### API 接口
- 系统状态查询
- PHP 版本列表
- PECL 扩展查询
- Composer 版本查询

## 项目结构

```
├── app/                    # Laravel 应用代码
│   ├── Admin/             # Dcat Admin 控制器
│   ├── Console/Commands/  # Artisan 命令
│   ├── Http/Controllers/  # Web 控制器
│   ├── Models/           # 数据模型
│   └── Services/         # 业务服务
├── database/             # 数据库文件
├── docker/              # Docker 配置
├── old/                 # 原版本代码
└── resources/           # 前端资源
```

## 贡献指南

欢迎提交 Issue 和 Pull Request 来帮助改进项目。

## 许可证

本项目基于 [MIT License](LICENSE) 开源。
