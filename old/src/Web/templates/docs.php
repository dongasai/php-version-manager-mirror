<!-- 文档导航 -->
<div class="row">
    <div class="col-md-3">
        <div class="list-group mb-4">
            <a href="#overview" class="list-group-item list-group-item-action active">概述</a>
            <a href="#installation" class="list-group-item list-group-item-action">安装</a>
            <a href="#configuration" class="list-group-item list-group-item-action">配置</a>
            <a href="#usage" class="list-group-item list-group-item-action">使用方法</a>
            <a href="#api" class="list-group-item list-group-item-action">API 接口</a>
            <a href="#faq" class="list-group-item list-group-item-action">常见问题</a>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">文档下载</h5>
            </div>
            <div class="card-body">
                <p>您可以下载文档的离线版本：</p>
                <a href="#" class="btn btn-outline-primary btn-sm btn-block">
                    <i class="fas fa-file-pdf"></i> PDF 格式
                </a>
                <a href="#" class="btn btn-outline-primary btn-sm btn-block mt-2">
                    <i class="fas fa-file-word"></i> DOCX 格式
                </a>
                <a href="#" class="btn btn-outline-primary btn-sm btn-block mt-2">
                    <i class="fas fa-file-alt"></i> Markdown 格式
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <!-- 概述 -->
        <div class="card mb-4" id="overview">
            <div class="card-header">
                <h5 class="mb-0">概述</h5>
            </div>
            <div class="card-body">
                <p>PVM 下载站是 PHP Version Manager (PVM) 的官方下载站点，提供以下内容：</p>
                <ul>
                    <li>PHP 源码包</li>
                    <li>PECL 扩展包</li>
                    <li>特定扩展源码</li>
                    <li>Composer 包</li>
                </ul>
                <p>使用 PVM 下载站可以加速 PHP 版本和扩展的下载和安装，特别适合网络环境不佳的地区。</p>
                
                <h6 class="mt-4">镜像内容</h6>
                <p>本下载站包含以下内容：</p>
                <ul>
                    <li>PHP 5.6.x - 8.3.x 的所有版本源码包</li>
                    <li>常用 PECL 扩展的所有版本</li>
                    <li>特定扩展（如 Redis, Memcached, Xdebug 等）的源码</li>
                    <li>Composer 1.x 和 2.x 的安装包</li>
                </ul>
            </div>
        </div>
        
        <!-- 安装 -->
        <div class="card mb-4" id="installation">
            <div class="card-header">
                <h5 class="mb-0">安装</h5>
            </div>
            <div class="card-body">
                <h6>安装 PVM</h6>
                <p>首先，您需要安装 PHP Version Manager (PVM)：</p>
                <pre class="bg-light p-3 rounded"><code>curl -o- https://raw.githubusercontent.com/yourusername/php-version-manager/master/install.sh | bash</code></pre>
                
                <h6 class="mt-4">配置镜像</h6>
                <p>安装完成后，您需要配置 PVM 使用本下载站：</p>
                <pre class="bg-light p-3 rounded"><code>pvm mirror config --php=https://your-mirror-url.com/php --pecl=https://your-mirror-url.com/pecl</code></pre>
                
                <p>或者手动编辑配置文件：</p>
                <pre class="bg-light p-3 rounded"><code>// 编辑 ~/.pvm/config/mirrors.php
return [
    'php' => [
        'official' => 'https://www.php.net/distributions',
        'mirrors' => [
            'local' => 'https://your-mirror-url.com/php',
        ],
        'default' => 'local',
    ],
    // 其他配置...
];</code></pre>
            </div>
        </div>
        
        <!-- 配置 -->
        <div class="card mb-4" id="configuration">
            <div class="card-header">
                <h5 class="mb-0">配置</h5>
            </div>
            <div class="card-body">
                <h6>镜像配置选项</h6>
                <p>PVM 支持以下镜像配置选项：</p>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>选项</th>
                                <th>说明</th>
                                <th>默认值</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>php</td>
                                <td>PHP 源码包镜像地址</td>
                                <td>https://www.php.net/distributions</td>
                            </tr>
                            <tr>
                                <td>pecl</td>
                                <td>PECL 扩展包镜像地址</td>
                                <td>https://pecl.php.net/get</td>
                            </tr>
                            <tr>
                                <td>extensions</td>
                                <td>特定扩展源码镜像地址</td>
                                <td>https://github.com</td>
                            </tr>
                            <tr>
                                <td>composer</td>
                                <td>Composer 包镜像地址</td>
                                <td>https://getcomposer.org/download</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- 使用方法 -->
        <div class="card mb-4" id="usage">
            <div class="card-header">
                <h5 class="mb-0">使用方法</h5>
            </div>
            <div class="card-body">
                <h6>安装 PHP 版本</h6>
                <p>使用 PVM 安装 PHP 版本：</p>
                <pre class="bg-light p-3 rounded"><code>pvm install 8.2.0</code></pre>
                
                <h6 class="mt-4">切换 PHP 版本</h6>
                <p>切换到指定的 PHP 版本：</p>
                <pre class="bg-light p-3 rounded"><code>pvm use 8.2.0</code></pre>
                
                <h6 class="mt-4">安装扩展</h6>
                <p>安装 PHP 扩展：</p>
                <pre class="bg-light p-3 rounded"><code>pvm ext install redis</code></pre>
                
                <h6 class="mt-4">使用 Composer</h6>
                <p>使用 PVM 安装的 Composer：</p>
                <pre class="bg-light p-3 rounded"><code>pvm composer install</code></pre>
            </div>
        </div>
        
        <!-- API 接口 -->
        <div class="card mb-4" id="api">
            <div class="card-header">
                <h5 class="mb-0">API 接口</h5>
            </div>
            <div class="card-body">
                <p>PVM 下载站提供以下 API 接口：</p>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>接口</th>
                                <th>说明</th>
                                <th>示例</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>/api/status.json</td>
                                <td>获取镜像状态</td>
                                <td><a href="/api/status.json" target="_blank">查看</a></td>
                            </tr>
                            <tr>
                                <td>/api/php.json</td>
                                <td>获取 PHP 源码包列表</td>
                                <td><a href="/api/php.json" target="_blank">查看</a></td>
                            </tr>
                            <tr>
                                <td>/api/pecl.json</td>
                                <td>获取 PECL 扩展包列表</td>
                                <td><a href="/api/pecl.json" target="_blank">查看</a></td>
                            </tr>
                            <tr>
                                <td>/api/extensions.json</td>
                                <td>获取特定扩展源码列表</td>
                                <td><a href="/api/extensions.json" target="_blank">查看</a></td>
                            </tr>
                            <tr>
                                <td>/api/composer.json</td>
                                <td>获取 Composer 包列表</td>
                                <td><a href="/api/composer.json" target="_blank">查看</a></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- 常见问题 -->
        <div class="card mb-4" id="faq">
            <div class="card-header">
                <h5 class="mb-0">常见问题</h5>
            </div>
            <div class="card-body">
                <div class="accordion" id="faqAccordion">
                    <div class="card">
                        <div class="card-header" id="faqHeading1">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#faqCollapse1" aria-expanded="true" aria-controls="faqCollapse1">
                                    如何更新镜像配置？
                                </button>
                            </h2>
                        </div>
                        <div id="faqCollapse1" class="collapse show" aria-labelledby="faqHeading1" data-parent="#faqAccordion">
                            <div class="card-body">
                                您可以使用 <code>pvm mirror config</code> 命令更新镜像配置，或者手动编辑 <code>~/.pvm/config/mirrors.php</code> 文件。
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header" id="faqHeading2">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                                    下载站支持哪些 PHP 版本？
                                </button>
                            </h2>
                        </div>
                        <div id="faqCollapse2" class="collapse" aria-labelledby="faqHeading2" data-parent="#faqAccordion">
                            <div class="card-body">
                                本下载站支持 PHP 5.6.x 到 8.3.x 的所有版本。
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-header" id="faqHeading3">
                            <h2 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
                                    如何贡献代码或报告问题？
                                </button>
                            </h2>
                        </div>
                        <div id="faqCollapse3" class="collapse" aria-labelledby="faqHeading3" data-parent="#faqAccordion">
                            <div class="card-body">
                                您可以在 <a href="https://github.com/dongasai/php-version-manager" target="_blank">GitHub</a> 上提交 Issue 或 Pull Request。
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 添加内联脚本
$inline_scripts = <<<JS
$(document).ready(function() {
    // 平滑滚动
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this.hash);
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 70
            }, 500);
            
            // 更新活动导航项
            $('.list-group-item').removeClass('active');
            $(this).addClass('active');
        }
    });
    
    // 滚动监听
    $(window).scroll(function() {
        var scrollPosition = $(window).scrollTop();
        
        $('div[id]').each(function() {
            var currentSection = $(this);
            var sectionTop = currentSection.offset().top - 100;
            
            if (scrollPosition >= sectionTop) {
                var currentId = currentSection.attr('id');
                $('.list-group-item').removeClass('active');
                $('.list-group-item[href="#' + currentId + '"]').addClass('active');
            }
        });
    });
});
JS;
?>
