@extends('layouts.app')

@section('title', '页面未找到 - PVM 下载站')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <!-- 404 图标 -->
            <div class="mb-4">
                <i class="fas fa-search fa-5x text-muted"></i>
            </div>
            
            <!-- 错误信息 -->
            <h1 class="display-4 fw-bold text-primary mb-3">404</h1>
            <h2 class="h4 mb-3">页面未找到</h2>
            <p class="lead text-muted mb-4">
                抱歉，您访问的文件或目录不存在。
            </p>
            
            <!-- 请求路径 -->
            @if(isset($path) && $path)
            <div class="alert alert-light border">
                <strong>请求路径：</strong>
                <code>/{{ $path }}</code>
            </div>
            @endif
            
            <!-- 可能的原因 -->
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="card border-0 h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-link fa-2x text-warning mb-3"></i>
                            <h6>链接错误</h6>
                            <p class="text-muted small">
                                链接可能已过期或输入错误
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-sync fa-2x text-info mb-3"></i>
                            <h6>正在同步</h6>
                            <p class="text-muted small">
                                文件可能正在同步中，请稍后再试
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-archive fa-2x text-secondary mb-3"></i>
                            <h6>已移除</h6>
                            <p class="text-muted small">
                                文件可能已被移除或重新组织
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 建议操作 -->
            <div class="row g-3 justify-content-center mb-4">
                <div class="col-auto">
                    <a href="{{ route('home') }}" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>返回首页
                    </a>
                </div>
                <div class="col-auto">
                    <button onclick="history.back()" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>返回上页
                    </button>
                </div>
                <div class="col-auto">
                    <a href="{{ route('status') }}" class="btn btn-outline-info">
                        <i class="fas fa-chart-line me-2"></i>查看状态
                    </a>
                </div>
            </div>
            
            <!-- 快速导航 -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-compass me-2"></i>快速导航
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="/php" class="btn btn-outline-primary w-100">
                                <i class="fab fa-php me-2"></i>PHP 源码
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/pecl" class="btn btn-outline-warning w-100">
                                <i class="fas fa-puzzle-piece me-2"></i>PECL 扩展
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/extensions" class="btn btn-outline-success w-100">
                                <i class="fab fa-github me-2"></i>GitHub 扩展
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="/composer" class="btn btn-outline-danger w-100">
                                <i class="fas fa-box me-2"></i>Composer 包
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 搜索建议 -->
            <div class="mt-4">
                <p class="text-muted">
                    <i class="fas fa-lightbulb me-1"></i>
                    提示：您可以尝试搜索相关文件，或查看
                    <a href="{{ route('docs') }}">使用文档</a>
                    了解正确的访问方式。
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 记录404错误用于分析
if (typeof gtag !== 'undefined') {
    gtag('event', 'page_not_found', {
        'page_path': window.location.pathname,
        'page_title': document.title
    });
}

// 自动建议相似路径
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const suggestions = [];
    
    // 基于当前路径生成建议
    if (currentPath.includes('php')) {
        suggestions.push({
            text: 'PHP 8.3 版本',
            url: '/php/8.3'
        });
        suggestions.push({
            text: 'PHP 8.2 版本',
            url: '/php/8.2'
        });
    } else if (currentPath.includes('pecl')) {
        suggestions.push({
            text: 'Redis 扩展',
            url: '/pecl/redis'
        });
        suggestions.push({
            text: 'MongoDB 扩展',
            url: '/pecl/mongodb'
        });
    }
    
    // 显示建议
    if (suggestions.length > 0) {
        const suggestionHtml = suggestions.map(s => 
            `<a href="${s.url}" class="btn btn-sm btn-outline-primary me-2 mb-2">${s.text}</a>`
        ).join('');
        
        const suggestionDiv = document.createElement('div');
        suggestionDiv.className = 'mt-3';
        suggestionDiv.innerHTML = `
            <p class="text-muted mb-2">
                <i class="fas fa-magic me-1"></i>您可能在寻找：
            </p>
            ${suggestionHtml}
        `;
        
        document.querySelector('.card-body').appendChild(suggestionDiv);
    }
});

// 键盘快捷键
document.addEventListener('keydown', function(e) {
    // 按 H 键返回首页
    if (e.key === 'h' || e.key === 'H') {
        window.location.href = '{{ route("home") }}';
    }
    
    // 按 B 键返回上页
    if (e.key === 'b' || e.key === 'B') {
        history.back();
    }
    
    // 按 S 键查看状态
    if (e.key === 's' || e.key === 'S') {
        window.location.href = '{{ route("status") }}';
    }
});
</script>
@endpush
