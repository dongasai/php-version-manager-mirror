@extends('layouts.app')

@section('title', 'PVM 下载站 - 首页')

@section('content')
<!-- 英雄区域 -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-4">PHP 版本管理器下载站</h1>
                <p class="lead mb-4">
                    提供 PHP 源码、PECL 扩展、GitHub 扩展等资源的高速下载服务。
                    支持多版本管理，助力 PHP 开发者快速构建开发环境。
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="/php" class="btn btn-light btn-lg">
                        <i class="fab fa-php me-2"></i>PHP 源码
                    </a>
                    <a href="/pecl" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-puzzle-piece me-2"></i>PECL 扩展
                    </a>
                    <a href="{{ route('docs') }}" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-book me-2"></i>使用文档
                    </a>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="stats-card card">
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="fas fa-download me-2"></i>下载统计
                        </h3>
                        <div class="row text-center">
                            <div class="col-6">
                                <h4 class="mb-1">{{ array_sum(array_column($stats, 'file_count')) }}</h4>
                                <small>总文件数</small>
                            </div>
                            <div class="col-6">
                                <h4 class="mb-1">{{ formatBytes(array_sum(array_column($stats, 'total_size'))) }}</h4>
                                <small>总大小</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 镜像服务 -->
<section class="py-5">
    <div class="container">
        <div class="row mb-5">
            <div class="col-12 text-center">
                <h2 class="fw-bold mb-3">镜像服务</h2>
                <p class="text-muted">提供多种 PHP 相关资源的镜像下载服务</p>
            </div>
        </div>
        
        <div class="row g-4">
            @foreach($mirrors as $mirror)
            <div class="col-lg-6">
                <div class="card mirror-card {{ $mirror->type }} h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="me-3">
                                @switch($mirror->type)
                                    @case('php')
                                        <i class="fab fa-php fa-2x text-primary"></i>
                                        @break
                                    @case('pecl')
                                        <i class="fas fa-puzzle-piece fa-2x text-warning"></i>
                                        @break
                                    @case('extension')
                                        <i class="fab fa-github fa-2x text-success"></i>
                                        @break
                                    @case('composer')
                                        <i class="fas fa-box fa-2x text-danger"></i>
                                        @break
                                @endswitch
                            </div>
                            <div>
                                <h5 class="card-title mb-1">{{ $mirror->name }}</h5>
                                <div class="d-flex align-items-center">
                                    <span class="status-indicator status-{{ $mirror->isEnabled() ? 'online' : 'offline' }}"></span>
                                    <small class="text-muted">
                                        {{ $mirror->isEnabled() ? '在线' : '离线' }}
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        @if(isset($stats[$mirror->type]))
                        <div class="row text-center mb-3">
                            <div class="col-4">
                                <div class="fw-bold">{{ $stats[$mirror->type]['file_count'] }}</div>
                                <small class="text-muted">文件数</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold">{{ formatBytes($stats[$mirror->type]['total_size']) }}</div>
                                <small class="text-muted">大小</small>
                            </div>
                            <div class="col-4">
                                <div class="fw-bold">
                                    @if($stats[$mirror->type]['last_updated'])
                                        {{ \Carbon\Carbon::parse($stats[$mirror->type]['last_updated'])->diffForHumans() }}
                                    @else
                                        未知
                                    @endif
                                </div>
                                <small class="text-muted">更新</small>
                            </div>
                        </div>
                        @endif
                        
                        <p class="card-text text-muted mb-3">
                            @switch($mirror->type)
                                @case('php')
                                    PHP 官方源码包，包含各个版本的完整源代码
                                    @break
                                @case('pecl')
                                    PECL 扩展包，提供丰富的 PHP 扩展功能
                                    @break
                                @case('extension')
                                    GitHub 上的热门 PHP 扩展项目
                                    @break
                                @case('composer')
                                    Composer 包管理器相关资源
                                    @break
                            @endswitch
                        </p>
                        
                        <div class="d-flex gap-2">
                            <a href="/{{ $mirror->type }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-folder-open me-1"></i>浏览文件
                            </a>
                            <a href="/api/{{ $mirror->type }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-code me-1"></i>API
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- 快速开始 -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="fw-bold mb-4">快速开始</h2>
                <p class="text-muted mb-4">几个简单的步骤，快速使用我们的镜像服务</p>
                
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="card border-0 h-100">
                            <div class="card-body text-center">
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                    <i class="fas fa-search fa-lg"></i>
                                </div>
                                <h5>1. 浏览资源</h5>
                                <p class="text-muted">浏览我们提供的 PHP 源码、扩展等资源</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 h-100">
                            <div class="card-body text-center">
                                <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                    <i class="fas fa-download fa-lg"></i>
                                </div>
                                <h5>2. 下载文件</h5>
                                <p class="text-muted">选择需要的版本，直接下载到本地</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 h-100">
                            <div class="card-body text-center">
                                <div class="bg-warning text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                    <i class="fas fa-cogs fa-lg"></i>
                                </div>
                                <h5>3. 配置使用</h5>
                                <p class="text-muted">按照文档说明配置和使用下载的资源</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <a href="{{ route('docs') }}" class="btn btn-primary btn-lg">
                        <i class="fas fa-book me-2"></i>查看详细文档
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 服务器信息 -->
@if(isset($serverConfig))
<section class="py-4 bg-dark text-light">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h6 class="mb-1">
                    <i class="fas fa-server me-2"></i>服务器信息
                </h6>
                <small class="text-muted">
                    地址: {{ $serverConfig['public_url'] ?? 'localhost' }} | 
                    端口: {{ $serverConfig['port'] ?? '8080' }}
                </small>
            </div>
            <div class="col-md-6 text-md-end">
                <small class="text-muted">
                    <i class="fas fa-clock me-1"></i>
                    最后更新: {{ now()->format('Y-m-d H:i:s') }}
                </small>
            </div>
        </div>
    </div>
</section>
@endif
@endsection

@push('scripts')
<script>
// 格式化字节大小的 JavaScript 函数
function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// 定期更新状态指示器
setInterval(function() {
    fetch('/api/status')
        .then(response => response.json())
        .then(data => {
            // 更新状态指示器
            document.querySelectorAll('.status-indicator').forEach(function(indicator) {
                // 这里可以根据 API 返回的数据更新状态
            });
        })
        .catch(error => {
            console.log('状态更新失败:', error);
        });
}, 30000); // 每30秒更新一次
</script>
@endpush

@php
// 定义格式化字节大小的辅助函数
function formatBytes($bytes, $precision = 2) {
    if ($bytes == 0) return '0 B';

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
}
@endphp
