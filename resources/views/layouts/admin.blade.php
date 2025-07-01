<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', '管理后台') - PVM 镜像站</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #343a40 0%, #495057 100%);
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: #adb5bd;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin: 0.25rem 0.5rem;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            text-align: center;
            margin-right: 0.5rem;
        }
        
        .main-content {
            margin-left: 0;
            transition: margin-left 0.3s ease;
        }
        
        @media (min-width: 768px) {
            .main-content {
                margin-left: 250px;
            }
        }
        
        .navbar-brand {
            font-weight: bold;
            color: #007bff !important;
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
            background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stats-card .card-body {
            padding: 1.5rem;
        }
        
        .stats-card h3 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            background-color: #f8f9fa;
        }
        
        .badge {
            font-size: 0.75rem;
        }
        
        .btn {
            border-radius: 0.375rem;
            font-weight: 500;
        }
        
        .progress {
            height: 8px;
            border-radius: 4px;
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
        
        .sidebar-toggle {
            display: none;
        }
        
        @media (max-width: 767px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                width: 250px;
                z-index: 1050;
                transition: left 0.3s ease;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar-toggle {
                display: block;
            }
        }
        
        .breadcrumb {
            background-color: transparent;
            padding: 0;
            margin-bottom: 1rem;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: ">";
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <!-- 侧边栏 -->
    <nav class="sidebar position-fixed d-md-block" id="sidebar">
        <div class="p-3">
            <a class="navbar-brand text-white" href="{{ route('admin.dashboard') }}">
                <i class="fas fa-cogs me-2"></i>管理后台
            </a>
        </div>
        
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('admin.dashboard')) active @endif" 
                   href="{{ route('admin.dashboard') }}">
                    <i class="fas fa-tachometer-alt"></i>仪表盘
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('admin.mirrors.*')) active @endif" 
                   href="{{ route('admin.mirrors.index') }}">
                    <i class="fas fa-mirror"></i>镜像管理
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('admin.jobs.*')) active @endif" 
                   href="{{ route('admin.jobs.index') }}">
                    <i class="fas fa-tasks"></i>同步任务
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('admin.configs.*')) active @endif" 
                   href="{{ route('admin.configs.index') }}">
                    <i class="fas fa-cog"></i>系统配置
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link @if(request()->routeIs('admin.logs.*')) active @endif" 
                   href="{{ route('admin.logs.index') }}">
                    <i class="fas fa-file-alt"></i>访问日志
                </a>
            </li>
            <li class="nav-item mt-3">
                <hr class="text-muted">
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('home') }}" target="_blank">
                    <i class="fas fa-external-link-alt"></i>前台首页
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('status') }}" target="_blank">
                    <i class="fas fa-chart-line"></i>状态监控
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/api/status" target="_blank">
                    <i class="fas fa-code"></i>API 接口
                </a>
            </li>
        </ul>
    </nav>

    <!-- 主要内容区域 -->
    <div class="main-content">
        <!-- 顶部导航栏 -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container-fluid">
                <button class="btn btn-outline-secondary sidebar-toggle me-3" 
                        type="button" 
                        onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="d-flex align-items-center">
                    <span class="navbar-text me-3">
                        <i class="fas fa-user-circle me-1"></i>管理员
                    </span>
                    <span class="navbar-text text-muted">
                        <i class="fas fa-clock me-1"></i>
                        <span id="currentTime">{{ now()->format('Y-m-d H:i:s') }}</span>
                    </span>
                </div>
            </div>
        </nav>

        <!-- 面包屑导航 -->
        @if(isset($breadcrumbs) && count($breadcrumbs) > 1)
        <div class="container-fluid mt-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    @foreach($breadcrumbs as $breadcrumb)
                        @if($loop->last)
                            <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb['name'] }}</li>
                        @else
                            <li class="breadcrumb-item">
                                <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['name'] }}</a>
                            </li>
                        @endif
                    @endforeach
                </ol>
            </nav>
        </div>
        @endif

        <!-- 页面内容 -->
        <div class="container-fluid py-4">
            @yield('content')
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // 切换侧边栏
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('show');
        }
        
        // 点击外部关闭侧边栏
        document.addEventListener('click', function(e) {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.sidebar-toggle');
            
            if (window.innerWidth < 768 && 
                !sidebar.contains(e.target) && 
                !toggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });
        
        // 更新当前时间
        function updateTime() {
            const now = new Date();
            const timeString = now.getFullYear() + '-' + 
                             String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                             String(now.getDate()).padStart(2, '0') + ' ' + 
                             String(now.getHours()).padStart(2, '0') + ':' + 
                             String(now.getMinutes()).padStart(2, '0') + ':' + 
                             String(now.getSeconds()).padStart(2, '0');
            
            const timeElement = document.getElementById('currentTime');
            if (timeElement) {
                timeElement.textContent = timeString;
            }
        }
        
        // 每秒更新时间
        setInterval(updateTime, 1000);
        
        // 自动刷新数据
        let autoRefreshEnabled = true;
        
        function toggleAutoRefresh() {
            autoRefreshEnabled = !autoRefreshEnabled;
            console.log('自动刷新:', autoRefreshEnabled ? '开启' : '关闭');
        }
        
        // 每30秒自动刷新数据
        setInterval(function() {
            if (autoRefreshEnabled && typeof refreshData === 'function') {
                refreshData();
            }
        }, 30000);
    </script>
    
    @stack('scripts')
</body>
</html>
