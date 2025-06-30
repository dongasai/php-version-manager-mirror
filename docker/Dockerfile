FROM ubuntu:22.04

# 设置非交互式安装
ENV DEBIAN_FRONTEND=noninteractive

# 使用阿里云镜像源
RUN sed -i 's/archive.ubuntu.com/mirrors.aliyun.com/g' /etc/apt/sources.list && \
    sed -i 's/security.ubuntu.com/mirrors.aliyun.com/g' /etc/apt/sources.list

# 安装基本工具和PHP
RUN apt-get update && apt-get install -y \
    curl \
    wget \
    git \
    unzip \
    build-essential \
    libssl-dev \
    libcurl4-openssl-dev \
    libxml2-dev \
    libpng-dev \
    libjpeg-dev \
    libzip-dev \
    libonig-dev \
    libsqlite3-dev \
    libreadline-dev \
    php8.1 \
    php8.1-cli \
    php8.1-common \
    php8.1-curl \
    php8.1-mbstring \
    php8.1-xml \
    php8.1-zip \
    php8.1-sqlite3 \
    php8.1-readline \
    sudo \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 创建pvm-mirror用户
RUN useradd -m -s /bin/bash pvm-mirror && \
    echo "pvm-mirror ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/pvm-mirror

# 设置工作目录
WORKDIR /app

# 复制项目文件
COPY . /app/

# 设置权限
RUN chown -R pvm-mirror:pvm-mirror /app && \
    chmod +x /app/bin/pvm-mirror

# 创建数据和日志目录
RUN mkdir -p /app/data /app/logs && \
    chown -R pvm-mirror:pvm-mirror /app/data /app/logs

# 切换到pvm-mirror用户
USER pvm-mirror

# 设置默认环境变量
ENV PVM_MIRROR_ENV=production \
    PVM_MIRROR_DEBUG=false \
    PVM_MIRROR_DATA_DIR=/app/data \
    PVM_MIRROR_LOG_DIR=/app/logs \
    PVM_MIRROR_CACHE_DIR=/app/cache \
    PVM_MIRROR_LOG_LEVEL=info \
    PVM_MIRROR_HOST=0.0.0.0 \
    PVM_MIRROR_PORT=34403 \
    PVM_MIRROR_PUBLIC_URL=http://localhost:34403 \
    PVM_MIRROR_MAX_CONNECTIONS=100 \
    PVM_MIRROR_TIMEOUT=30 \
    PVM_MIRROR_ENABLE_HTTPS=false \
    PVM_MIRROR_CACHE_SIZE=104857600 \
    PVM_MIRROR_CACHE_TTL=3600 \
    PVM_MIRROR_SYNC_INTERVAL=24 \
    PVM_MIRROR_MAX_RETRIES=3 \
    PVM_MIRROR_RETRY_INTERVAL=300

# 暴露端口 (使用环境变量)
EXPOSE $PVM_MIRROR_PORT

# 健康检查 (使用环境变量)
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:$PVM_MIRROR_PORT/ || exit 1

# 创建启动脚本
COPY --chown=pvm-mirror:pvm-mirror docker/pvm-mirror/entrypoint.sh /app/entrypoint.sh
RUN chmod +x /app/entrypoint.sh

# 启动命令
ENTRYPOINT ["/app/entrypoint.sh"]
CMD ["server", "start", "--foreground"]
