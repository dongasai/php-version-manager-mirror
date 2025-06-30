# 更新日志

本文档记录了PHP Version Manager Mirror项目的所有重要变更。

格式基于 [Keep a Changelog](https://keepachangelog.com/zh-CN/1.0.0/)，
版本号遵循 [语义化版本](https://semver.org/lang/zh-CN/)。

## [未发布]

### 新增
- 从PVM主项目分离出独立的镜像服务
- 完整的Docker容器化支持
- 支持多架构构建（AMD64、ARM64）
- GitHub Actions自动化CI/CD流程
- 完整的项目文档和API文档
- 单元测试和集成测试框架

### 变更
- 重构目录结构，采用标准的PHP项目布局
- 使用Composer进行依赖管理和自动加载
- 优化Docker镜像构建流程
- 改进配置管理和环境变量支持

### 修复
- 修复配置文件路径引用问题
- 优化自动加载器的路径解析
- 改进错误处理和日志记录

## [1.0.0] - 2025-06-30

### 新增
- 初始版本发布
- 支持PHP源码镜像同步
- 支持PECL扩展镜像同步
- 支持Composer包镜像同步
- Web管理界面
- RESTful API接口
- Docker容器化部署
- 多环境配置支持

### 功能特性
- 智能同步机制，支持增量更新
- 内置缓存系统，提高访问性能
- 支持并发下载和断点续传
- 完整的监控和日志系统
- 灵活的配置管理
- 跨平台支持

### 技术栈
- PHP 7.1+ 兼容
- 无外部依赖，纯PHP实现
- Docker多阶段构建
- GitHub Actions CI/CD
- PSR-4自动加载
- PSR-12代码规范
