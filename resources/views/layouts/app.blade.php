<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PVM 下载站')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .navbar-brand {
            font-weight: bold;
            color: #007bff !important;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
        }
        
        .card {
            border: none;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            transition: box-shadow 0.15s ease-in-out;
        }
        
        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .stats-card {
            background: linear-gradient(45deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .stats-card .card-body {
            padding: 2rem;
        }
        
        .mirror-card {
            border-left: 4px solid #007bff;
        }
        
        .mirror-card.php {
            border-left-color: #6f42c1;
        }
        
        .mirror-card.pecl {
            border-left-color: #fd7e14;
        }
        
        .mirror-card.extension {
            border-left-color: #20c997;
        }
        
        .mirror-card.composer {
            border-left-color: #dc3545;
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: ">";
        }
        
        .file-list {
            background: white;
            border-radius: 0.375rem;
            overflow: hidden;
        }
        
        .file-item {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #dee2e6;
            transition: background-color 0.15s ease-in-out;
        }
        
        .file-item:hover {
            background-color: #f8f9fa;
        }
        
        .file-item:last-child {
            border-bottom: none;
        }
        
        .file-icon {
            width: 20px;
            text-align: center;
            margin-right: 0.5rem;
        }
        
        .file-size {
            color: #6c757d;
            font-size: 0.875rem;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }
        
        .status-online {
            background-color: #28a745;
        }
        
        .status-offline {
            background-color: #dc3545;
        }
        
        .status-syncing {
            background-color: #ffc107;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .footer {
            background-color: #343a40;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        .progress-bar {
            transition: width 0.3s ease;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
        }
        
        .badge {
            font-size: 0.75rem;
        }
        
        .alert {
            border: none;
            border-radius: 0.5rem;
        }
        
        .btn {
            border-radius: 0.375rem;
            font-weight: 500;
        }
        
        .navbar-nav .nav-link {
            font-weight: 500;
            transition: color 0.15s ease-in-out;
        }
        
        .navbar-nav .nav-link:hover {
            color: #007bff !important;
        }
        
        .navbar-nav .nav-link.active {
            color: #007bff !important;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 2rem 0;
            }
            
            .stats-card .card-body {
                padding: 1.5rem;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="{{ route('home') }}">
                <i class="fas fa-download me-2"></i>PVM 下载站
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('home')) active @endif" href="{{ route('home') }}">
                            <i class="fas fa-home me-1"></i>首页
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(str_starts_with(request()->path(), 'php')) active @endif" href="/php">
                            <i class="fab fa-php me-1"></i>PHP 源码
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(str_starts_with(request()->path(), 'pecl')) active @endif" href="/pecl">
                            <i class="fas fa-puzzle-piece me-1"></i>PECL 扩展
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(str_starts_with(request()->path(), 'extensions')) active @endif" href="/extensions">
                            <i class="fab fa-github me-1"></i>GitHub 扩展
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(str_starts_with(request()->path(), 'composer')) active @endif" href="/composer">
                            <i class="fas fa-box me-1"></i>Composer 包
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('status')) active @endif" href="{{ route('status') }}">
                            <i class="fas fa-chart-line me-1"></i>状态监控
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link @if(request()->routeIs('docs')) active @endif" href="{{ route('docs') }}">
                            <i class="fas fa-book me-1"></i>文档
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 面包屑导航 -->
    @if(isset($breadcrumbs) && count($breadcrumbs) > 1)
    <div class="container mt-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                @foreach($breadcrumbs as $breadcrumb)
                    @if($loop->last)
                        <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb['name'] }}</li>
                    @else
                        <li class="breadcrumb-item">
                            <a href="{{ $breadcrumb['path'] }}">{{ $breadcrumb['name'] }}</a>
                        </li>
                    @endif
                @endforeach
            </ol>
        </nav>
    </div>
    @endif

    <!-- 主要内容 -->
    <main>
        @yield('content')
    </main>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>PVM 下载站</h5>
                    <p class="mb-0">PHP 版本管理器镜像站，提供 PHP 源码、PECL 扩展、GitHub 扩展等下载服务。</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <a href="{{ route('status') }}" class="text-light me-3">
                            <i class="fas fa-chart-line me-1"></i>状态监控
                        </a>
                        <a href="{{ route('docs') }}" class="text-light me-3">
                            <i class="fas fa-book me-1"></i>使用文档
                        </a>
                        <a href="/api/status" class="text-light">
                            <i class="fas fa-code me-1"></i>API
                        </a>
                    </p>
                    <small class="text-muted">Powered by Laravel & PVM</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    @stack('scripts')
</body>
</html>
