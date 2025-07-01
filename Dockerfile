# 使用官方 PHP Apache 镜像
FROM php:8.1-apache

# 设置工作目录
WORKDIR /var/www/html

# 安装系统依赖和PHP扩展
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    sqlite3 libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite mbstring exif pcntl bcmath gd \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 复制应用代码
COPY . /var/www/html

# 安装依赖并设置权限
RUN composer install --no-dev --optimize-autoloader \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 设置 Apache DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# 暴露端口
EXPOSE 80

# 启动命令
CMD ["apache2-foreground"]
