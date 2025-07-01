@extends('layouts.app')

@section('title', '目录列表: /' . $path . ' - PVM 下载站')

@section('content')
<div class="container py-4">
    <!-- 页面标题 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="mb-1">
                        <i class="fas fa-folder-open me-2 text-primary"></i>
                        目录列表
                    </h2>
                    <p class="text-muted mb-0">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        /{{ $path }}
                    </p>
                </div>
                <div>
                    @if($filterApplied)
                    <span class="badge bg-info">
                        <i class="fas fa-filter me-1"></i>{{ $filterDescription }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 筛选器 -->
    @if(str_contains($path, 'php') || str_contains($path, 'pecl'))
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-6">
                            <label for="version" class="form-label">版本筛选</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="version" 
                                   name="version" 
                                   value="{{ $queryParams['version'] ?? '' }}" 
                                   placeholder="输入版本号，如: 8.3, 7.4">
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>筛选
                                </button>
                                @if($filterApplied)
                                <a href="{{ request()->url() }}" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>清除
                                </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- 文件列表 -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h6 class="mb-0">
                                <i class="fas fa-list me-2"></i>
                                文件列表 ({{ count($items) }} 项)
                            </h6>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                点击文件名下载，点击文件夹进入
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="file-list">
                    @if(empty($items))
                    <div class="text-center py-5">
                        <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">目录为空</h5>
                        <p class="text-muted">
                            @if($filterApplied)
                                没有找到匹配的文件，请尝试调整筛选条件
                            @else
                                此目录暂无文件
                            @endif
                        </p>
                    </div>
                    @else
                    @foreach($items as $item)
                    <div class="file-item">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="d-flex align-items-center">
                                    <div class="file-icon">
                                        @if($item['type'] === 'directory')
                                            <i class="fas fa-folder text-warning"></i>
                                        @else
                                            @php
                                                $extension = pathinfo($item['name'], PATHINFO_EXTENSION);
                                            @endphp
                                            @switch($extension)
                                                @case('gz')
                                                @case('tgz')
                                                @case('tar')
                                                    <i class="fas fa-file-archive text-info"></i>
                                                    @break
                                                @case('zip')
                                                    <i class="fas fa-file-zipper text-info"></i>
                                                    @break
                                                @case('php')
                                                    <i class="fab fa-php text-primary"></i>
                                                    @break
                                                @case('json')
                                                    <i class="fas fa-file-code text-success"></i>
                                                    @break
                                                @default
                                                    <i class="fas fa-file text-secondary"></i>
                                            @endswitch
                                        @endif
                                    </div>
                                    <div>
                                        @if($item['type'] === 'directory')
                                            <a href="/{{ $path }}/{{ $item['name'] }}" 
                                               class="text-decoration-none fw-medium">
                                                {{ $item['name'] }}/
                                            </a>
                                        @else
                                            <a href="/{{ $path }}/{{ $item['name'] }}" 
                                               class="text-decoration-none"
                                               download>
                                                {{ $item['name'] }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 text-md-center">
                                @if($item['type'] === 'file' && isset($item['size']))
                                <span class="file-size">
                                    {{ formatBytes($item['size']) }}
                                </span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </div>
                            <div class="col-md-3 text-md-end">
                                @if(isset($item['modified']))
                                <small class="text-muted">
                                    {{ date('Y-m-d H:i', $item['modified']) }}
                                </small>
                                @else
                                <small class="text-muted">-</small>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- 帮助信息 -->
    @if(str_contains($path, 'php'))
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h6 class="alert-heading">
                    <i class="fas fa-lightbulb me-2"></i>使用提示
                </h6>
                <p class="mb-2">
                    <strong>PHP 源码下载：</strong>选择对应版本的 .tar.gz 文件下载，解压后即可编译安装。
                </p>
                <p class="mb-0">
                    <strong>版本说明：</strong>建议下载最新的稳定版本，开发环境可以选择 RC 或 beta 版本。
                </p>
            </div>
        </div>
    </div>
    @elseif(str_contains($path, 'pecl'))
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-success">
                <h6 class="alert-heading">
                    <i class="fas fa-puzzle-piece me-2"></i>PECL 扩展说明
                </h6>
                <p class="mb-2">
                    <strong>安装方法：</strong>下载 .tgz 文件后，使用 <code>pecl install package.tgz</code> 安装。
                </p>
                <p class="mb-0">
                    <strong>依赖关系：</strong>某些扩展可能需要系统库支持，请查看扩展文档。
                </p>
            </div>
        </div>
    </div>
    @elseif(str_contains($path, 'extensions'))
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-warning">
                <h6 class="alert-heading">
                    <i class="fab fa-github me-2"></i>GitHub 扩展说明
                </h6>
                <p class="mb-2">
                    <strong>源码编译：</strong>这些是从 GitHub 获取的扩展源码，需要手动编译安装。
                </p>
                <p class="mb-0">
                    <strong>编译步骤：</strong>解压后执行 <code>phpize && ./configure && make && make install</code>
                </p>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// 文件大小排序
function sortBySize() {
    // 实现文件大小排序逻辑
}

// 文件名排序
function sortByName() {
    // 实现文件名排序逻辑
}

// 修改时间排序
function sortByDate() {
    // 实现修改时间排序逻辑
}

// 键盘快捷键支持
document.addEventListener('keydown', function(e) {
    // 按 / 键聚焦到搜索框
    if (e.key === '/' && !e.ctrlKey && !e.metaKey) {
        e.preventDefault();
        const versionInput = document.getElementById('version');
        if (versionInput) {
            versionInput.focus();
        }
    }
    
    // 按 Esc 键清除筛选
    if (e.key === 'Escape') {
        const clearButton = document.querySelector('a[href="{{ request()->url() }}"]');
        if (clearButton) {
            window.location.href = clearButton.href;
        }
    }
});

// 文件下载统计
document.querySelectorAll('a[download]').forEach(function(link) {
    link.addEventListener('click', function() {
        // 可以在这里添加下载统计逻辑
        console.log('下载文件:', this.getAttribute('href'));
    });
});
</script>
@endpush

@php
// 定义格式化字节大小的辅助函数
if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
@endphp
