<?php

/**
 * PVM 镜像内容配置文件
 *
 * 用于配置需要镜像的内容，包括PHP版本、扩展等
 * 版本信息已移至独立的配置文件中
 */

return [
    // 版本发现配置
    'discovery' => [
        // 是否启用自动版本发现
        'enabled' => true,

        // API 调用超时时间（秒）
        'timeout' => 30,

        // 缓存时间（秒），0表示不缓存
        'cache_ttl' => 3600,

        // 是否使用配置文件作为版本发现的补充
        'use_config_fallback' => true,

        // 是否只获取稳定版本
        'stable_only' => true,
    ],

    // PHP 源码镜像配置
    'php' => [
        // 官方源
        'source' => 'https://www.php.net/distributions',

        // 版本发现 API
        'discovery_api' => 'https://www.php.net/releases/index.php?json=1',

        // 文件名模式
        'pattern' => 'php-{version}.tar.gz',

        // 版本配置文件路径
        'version_config' => 'extensions/php/versions.php',

        // 是否启用此镜像
        'enabled' => true,
    ],
    // PECL 扩展镜像配置
    'pecl' => [
        // 官方源
        'source' => 'https://pecl.php.net/get',

        // 文件名模式
        'pattern' => '{extension}-{version}.tgz',

        // 扩展配置目录
        'config_dir' => 'extensions/pecl',

        // 支持的扩展列表 (132个扩展)
        'extensions' => [
            // 缓存扩展
            'apcu', 'apcu_bc', 'memcache', 'memcached', 'redis', 'relay', 'yac',

            // 数据库扩展
            'cassandra', 'dba', 'interbase', 'mongo', 'mongodb', 'mssql', 'mysql', 'mysqli',
            'oci8', 'odbc', 'pdo_dblib', 'pdo_firebird', 'pdo_mysql', 'pdo_oci', 'pdo_odbc',
            'pdo_pgsql', 'pdo_sqlsrv', 'pgsql', 'sqlsrv', 'sybase_ct',

            // 调试和性能
            'blackfire', 'ddtrace', 'excimer', 'memprof', 'opcache', 'pcov', 'spx', 'xdebug', 'xhprof',

            // 图像处理
            'exif', 'gd', 'gmagick', 'imagick', 'vips',

            // 网络和通信
            'amqp', 'curl', 'grpc', 'http', 'imap', 'ldap', 'mosquitto', 'oauth', 'openswoole',
            'smbclient', 'snmp', 'soap', 'sockets', 'ssh2', 'stomp', 'swoole',

            // 数据格式
            'csv', 'json_post', 'msgpack', 'protobuf', 'simdjson', 'wddx', 'xmldiff', 'xmlrpc', 'xsl', 'yaml',

            // 字符串和编码
            'enchant', 'gettext', 'igbinary', 'iconv', 'intl', 'mbstring', 'pspell', 'recode', 'tidy',

            // 加密和安全
            'gnupg', 'mcrypt', 'openssl', 'sodium',

            // 数学计算
            'bcmath', 'decimal', 'gmp',

            // 文件和压缩
            'bz2', 'lz4', 'lzf', 'snappy', 'zip', 'zstd',

            // 系统功能
            'calendar', 'ffi', 'inotify', 'parallel', 'pcntl', 'shmop', 'sysvmsg', 'sysvsem', 'sysvshm',
            'uopz', 'uploadprogress', 'uuid',

            // 事件处理
            'ev', 'event',

            // 框架扩展
            'phalcon', 'yaf', 'yar', 'zephir_parser',

            // 语言工具
            'ast', 'ds', 'parle', 'php_trie',

            // 日志
            'seaslog',

            // 地理和位置
            'gearman', 'geoip', 'geos', 'geospatial', 'maxminddb',

            // 机器学习
            'tensor',

            // 搜索
            'solr',

            // 消息队列
            'rdkafka', 'zmq', 'zookeeper',

            // 邮件处理
            'mailparse',

            // 标记语言
            'cmark',

            // 脚本引擎
            'luasandbox',

            // 办公文档
            'xlswriter',

            // 时间处理
            'timezonedb',

            // 安全防护
            'snuffleupagus', 'sourceguardian', 'ioncube_loader',

            // 监控追踪
            'opencensus', 'opentelemetry',

            // 其他工具
            'ion', 'jsmin', 'propro', 'pthreads', 'raphf', 'xdiff'
        ],

        // 是否启用此镜像
        'enabled' => true,
    ],
    // GitHub 扩展镜像配置
    'extensions' => [
        // 扩展配置目录
        'config_dir' => 'extensions/github',

        // 支持的扩展列表 (GitHub 扩展)
        'extensions' => [
            // 缓存和数据库
            'redis', 'memcached', 'mongodb', 'relay',

            // 调试和性能
            'xdebug', 'tideways', 'blackfire', 'ddtrace', 'excimer', 'memprof', 'pcov', 'spx', 'xhprof',

            // 图像和媒体
            'imagick', 'gmagick', 'vips',

            // 网络和通信
            'swoole', 'openswoole', 'grpc', 'amqp', 'mosquitto', 'ssh2', 'stomp',

            // 数据格式
            'msgpack', 'protobuf', 'simdjson', 'yaml',

            // 框架扩展
            'phalcon', 'yaf', 'yar', 'zephir_parser',

            // 语言工具
            'ast', 'ds', 'parle', 'php_trie',

            // 日志和监控
            'seaslog', 'opencensus', 'opentelemetry',

            // 地理和位置
            'gearman', 'geoip', 'geos', 'geospatial', 'maxminddb',

            // 机器学习
            'tensor',

            // 搜索
            'solr',

            // 消息队列
            'rdkafka', 'zmq', 'zookeeper',

            // 邮件处理
            'mailparse',

            // 标记语言
            'cmark',

            // 办公文档
            'xlswriter',

            // 文件和压缩
            'lz4', 'lzf', 'snappy', 'zstd',

            // 系统功能
            'parallel', 'uopz', 'uploadprogress', 'uuid',

            // 其他工具
            'ion', 'jsmin', 'xdiff'
        ],

        // 是否启用此镜像
        'enabled' => true,
    ],
    // Composer 镜像配置
    'composer' => [
        // 官方源
        'source' => 'https://getcomposer.org/download',

        // 文件名模式 (用于本地存储)
        'pattern' => 'composer-{version}.phar',

        // URL 模式 (用于下载)
        'url_pattern' => '{source}/{version}/composer.phar',

        // 版本配置文件路径
        'version_config' => 'composer/versions.php',

        // 是否启用此镜像
        'enabled' => true,
    ],
];
