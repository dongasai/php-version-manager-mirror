@extends('layouts.app')

@section('title', '使用文档 - PVM 下载站')

@section('content')
<div class="container py-4">
    <!-- 页面标题 -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-book me-2 text-primary"></i>
                使用文档
            </h2>
            <p class="text-muted mb-0">PVM 镜像站使用指南和API文档</p>
        </div>
    </div>

    <div class="row">
        <!-- 侧边栏导航 -->
        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">文档导航</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#overview" class="list-group-item list-group-item-action">
                        <i class="fas fa-eye me-2"></i>概述
                    </a>
                    <a href="#php-usage" class="list-group-item list-group-item-action">
                        <i class="fab fa-php me-2"></i>PHP 源码使用
                    </a>
                    <a href="#pecl-usage" class="list-group-item list-group-item-action">
                        <i class="fas fa-puzzle-piece me-2"></i>PECL 扩展使用
                    </a>
                    <a href="#extension-usage" class="list-group-item list-group-item-action">
                        <i class="fab fa-github me-2"></i>GitHub 扩展使用
                    </a>
                    <a href="#api-docs" class="list-group-item list-group-item-action">
                        <i class="fas fa-code me-2"></i>API 文档
                    </a>
                    <a href="#faq" class="list-group-item list-group-item-action">
                        <i class="fas fa-question-circle me-2"></i>常见问题
                    </a>
                </div>
            </div>
        </div>

        <!-- 主要内容 -->
        <div class="col-lg-9">
            <!-- 概述 -->
            <section id="overview" class="mb-5">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="fas fa-eye me-2 text-primary"></i>概述
                        </h3>
                        <p>PVM 镜像站是一个专为 PHP 开发者提供的资源下载服务，包含：</p>
                        <ul>
                            <li><strong>PHP 源码包</strong>：各个版本的 PHP 官方源代码</li>
                            <li><strong>PECL 扩展</strong>：PHP 扩展社区库中的扩展包</li>
                            <li><strong>GitHub 扩展</strong>：热门的第三方 PHP 扩展</li>
                            <li><strong>Composer 包</strong>：常用的 Composer 依赖包</li>
                        </ul>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            所有资源都经过完整性验证，确保下载的文件安全可靠。
                        </div>
                    </div>
                </div>
            </section>

            <!-- PHP 源码使用 -->
            <section id="php-usage" class="mb-5">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="fab fa-php me-2 text-primary"></i>PHP 源码使用
                        </h3>
                        
                        <h5>下载和编译</h5>
                        <p>1. 浏览 <a href="/php">PHP 源码目录</a>，选择需要的版本</p>
                        <p>2. 下载对应的 .tar.gz 文件</p>
                        <p>3. 解压并编译安装：</p>
                        
                        <div class="bg-dark text-light p-3 rounded">
                            <code>
# 解压源码包<br>
tar -xzf php-8.3.0.tar.gz<br>
cd php-8.3.0<br><br>

# 配置编译选项<br>
./configure --prefix=/usr/local/php --enable-fpm --with-mysql<br><br>

# 编译和安装<br>
make && make install
                            </code>
                        </div>
                        
                        <h5 class="mt-4">版本选择建议</h5>
                        <ul>
                            <li><strong>生产环境</strong>：选择最新的稳定版本（如 8.3.x）</li>
                            <li><strong>开发环境</strong>：可以尝试 RC 或 beta 版本</li>
                            <li><strong>兼容性</strong>：根据项目需求选择合适的主版本</li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- PECL 扩展使用 -->
            <section id="pecl-usage" class="mb-5">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="fas fa-puzzle-piece me-2 text-warning"></i>PECL 扩展使用
                        </h3>
                        
                        <h5>安装方法</h5>
                        <p>1. 浏览 <a href="/pecl">PECL 扩展目录</a>，找到需要的扩展</p>
                        <p>2. 下载对应版本的 .tgz 文件</p>
                        <p>3. 使用 PECL 命令安装：</p>
                        
                        <div class="bg-dark text-light p-3 rounded">
                            <code>
# 直接安装下载的包<br>
pecl install redis-5.3.7.tgz<br><br>

# 或者手动编译<br>
tar -xzf redis-5.3.7.tgz<br>
cd redis-5.3.7<br>
phpize<br>
./configure<br>
make && make install
                            </code>
                        </div>
                        
                        <h5 class="mt-4">常用扩展</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <ul>
                                    <li>redis - Redis 客户端</li>
                                    <li>mongodb - MongoDB 驱动</li>
                                    <li>imagick - 图像处理</li>
                                    <li>xdebug - 调试工具</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul>
                                    <li>swoole - 异步网络框架</li>
                                    <li>memcached - 缓存扩展</li>
                                    <li>zip - ZIP 文件处理</li>
                                    <li>gd - 图像处理库</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- GitHub 扩展使用 -->
            <section id="extension-usage" class="mb-5">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="fab fa-github me-2 text-success"></i>GitHub 扩展使用
                        </h3>
                        
                        <p>GitHub 扩展是从开源项目获取的 PHP 扩展源码，需要手动编译安装。</p>
                        
                        <h5>编译步骤</h5>
                        <div class="bg-dark text-light p-3 rounded">
                            <code>
# 解压源码包<br>
tar -xzf extension-name-1.0.0.tar.gz<br>
cd extension-name-1.0.0<br><br>

# 生成配置脚本<br>
phpize<br><br>

# 配置和编译<br>
./configure<br>
make && make install<br><br>

# 在 php.ini 中启用扩展<br>
echo "extension=extension_name.so" >> /etc/php.ini
                            </code>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            某些扩展可能需要额外的系统依赖库，请查看项目的 README 文档。
                        </div>
                    </div>
                </div>
            </section>

            <!-- API 文档 -->
            <section id="api-docs" class="mb-5">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="fas fa-code me-2 text-info"></i>API 文档
                        </h3>
                        
                        <p>PVM 镜像站提供 RESTful API 接口，返回 JSON 格式数据。</p>
                        
                        <h5>API 端点</h5>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>端点</th>
                                        <th>描述</th>
                                        <th>示例</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>/api/status</code></td>
                                        <td>获取系统状态</td>
                                        <td><a href="/api/status" target="_blank">查看</a></td>
                                    </tr>
                                    <tr>
                                        <td><code>/api/php</code></td>
                                        <td>获取 PHP 版本列表</td>
                                        <td><a href="/api/php" target="_blank">查看</a></td>
                                    </tr>
                                    <tr>
                                        <td><code>/api/pecl</code></td>
                                        <td>获取 PECL 扩展列表</td>
                                        <td><a href="/api/pecl" target="_blank">查看</a></td>
                                    </tr>
                                    <tr>
                                        <td><code>/api/extensions</code></td>
                                        <td>获取 GitHub 扩展列表</td>
                                        <td><a href="/api/extensions" target="_blank">查看</a></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <h5>使用示例</h5>
                        <div class="bg-dark text-light p-3 rounded">
                            <code>
# 获取系统状态<br>
curl -X GET {{ url('/api/status') }}<br><br>

# 获取 PHP 版本列表<br>
curl -X GET {{ url('/api/php') }}<br><br>

# 使用 JavaScript<br>
fetch('/api/status')<br>
&nbsp;&nbsp;.then(response => response.json())<br>
&nbsp;&nbsp;.then(data => console.log(data));
                            </code>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 常见问题 -->
            <section id="faq" class="mb-5">
                <div class="card">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="fas fa-question-circle me-2 text-secondary"></i>常见问题
                        </h3>
                        
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                        如何选择合适的 PHP 版本？
                                    </button>
                                </h2>
                                <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        建议根据项目需求选择：
                                        <ul>
                                            <li>新项目：使用最新稳定版本（PHP 8.3+）</li>
                                            <li>现有项目：根据兼容性要求选择</li>
                                            <li>生产环境：避免使用 RC 或 beta 版本</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                        编译时遇到依赖问题怎么办？
                                    </button>
                                </h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        常见解决方案：
                                        <ul>
                                            <li>安装开发工具包：<code>yum groupinstall "Development Tools"</code></li>
                                            <li>安装必要的库：<code>yum install libxml2-devel openssl-devel</code></li>
                                            <li>检查 configure 选项是否正确</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                        如何验证下载文件的完整性？
                                    </button>
                                </h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        我们的系统会自动验证文件完整性，但您也可以：
                                        <ul>
                                            <li>检查文件大小是否合理</li>
                                            <li>使用 <code>file</code> 命令检查文件类型</li>
                                            <li>尝试解压验证文件结构</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 平滑滚动到锚点
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// 高亮当前章节
window.addEventListener('scroll', function() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.list-group-item-action');
    
    let current = '';
    sections.forEach(section => {
        const sectionTop = section.offsetTop;
        const sectionHeight = section.clientHeight;
        if (window.pageYOffset >= sectionTop - 200) {
            current = section.getAttribute('id');
        }
    });
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === '#' + current) {
            link.classList.add('active');
        }
    });
});
</script>
@endpush
