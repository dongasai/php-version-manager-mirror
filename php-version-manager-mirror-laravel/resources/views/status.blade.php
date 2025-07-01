@extends('layouts.app')

@section('title', '状态监控 - PVM 下载站')

@section('content')
<div class="container py-4">
    <!-- 页面标题 -->
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-1">
                <i class="fas fa-chart-line me-2 text-primary"></i>
                镜像状态监控
            </h2>
            <p class="text-muted mb-0">实时监控镜像服务状态和同步任务进度</p>
        </div>
    </div>

    <!-- 系统概览 -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-server fa-2x text-primary mb-2"></i>
                    <h5 class="card-title">{{ count($mirrorStats) }}</h5>
                    <p class="card-text text-muted">镜像服务</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-tasks fa-2x text-success mb-2"></i>
                    <h5 class="card-title">{{ $jobStats['total'] }}</h5>
                    <p class="card-text text-muted">总任务数</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-play fa-2x text-warning mb-2"></i>
                    <h5 class="card-title">{{ $jobStats['running'] }}</h5>
                    <p class="card-text text-muted">运行中</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="fas fa-check fa-2x text-info mb-2"></i>
                    <h5 class="card-title">{{ $jobStats['completed'] }}</h5>
                    <p class="card-text text-muted">已完成</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 镜像状态 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-mirror me-2"></i>镜像状态
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        @foreach($mirrorStats as $mirrorStat)
                        @php
                            $mirror = $mirrorStat['mirror'];
                            $stats = $mirrorStat['stats'];
                            $latestJob = $mirrorStat['latest_job'];
                        @endphp
                        <div class="col-lg-6">
                            <div class="card border-start border-4 border-{{ $mirror->isEnabled() ? 'success' : 'danger' }}">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="d-flex align-items-center">
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
                                                <h6 class="mb-1">{{ $mirror->name }}</h6>
                                                <div class="d-flex align-items-center">
                                                    <span class="status-indicator status-{{ $mirror->isEnabled() ? 'online' : 'offline' }}"></span>
                                                    <small class="text-muted">
                                                        {{ $mirror->isEnabled() ? '在线' : '离线' }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            @if($latestJob && $latestJob->isRunning())
                                            <span class="badge bg-warning">
                                                <i class="fas fa-sync fa-spin me-1"></i>同步中
                                            </span>
                                            @elseif($latestJob && $latestJob->isCompleted())
                                            <span class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>已完成
                                            </span>
                                            @elseif($latestJob && $latestJob->isFailed())
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times me-1"></i>失败
                                            </span>
                                            @else
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-minus me-1"></i>无任务
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <div class="row text-center mb-3">
                                        <div class="col-4">
                                            <div class="fw-bold">{{ $stats['file_count'] }}</div>
                                            <small class="text-muted">文件数</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="fw-bold">{{ formatBytes($stats['total_size']) }}</div>
                                            <small class="text-muted">总大小</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="fw-bold">
                                                @if($stats['last_updated'])
                                                    {{ \Carbon\Carbon::parse($stats['last_updated'])->diffForHumans() }}
                                                @else
                                                    未知
                                                @endif
                                            </div>
                                            <small class="text-muted">更新时间</small>
                                        </div>
                                    </div>
                                    
                                    @if($latestJob)
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <small class="text-muted">最新任务进度</small>
                                            <small class="text-muted">{{ $latestJob->progress }}%</small>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-{{ $latestJob->status_color }}" 
                                                 style="width: {{ $latestJob->progress }}%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            任务 #{{ $latestJob->id }} - {{ $latestJob->status_name }}
                                        </small>
                                        @if($latestJob->duration)
                                        <small class="text-muted">
                                            耗时: {{ $latestJob->duration }}
                                        </small>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 系统信息 -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>系统信息
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted">数据目录:</td>
                            <td><code>{{ $systemInfo['data_dir'] }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">缓存目录:</td>
                            <td><code>{{ $systemInfo['cache_dir'] }}</code></td>
                        </tr>
                        <tr>
                            <td class="text-muted">服务器地址:</td>
                            <td>{{ $systemInfo['server_config']['public_url'] ?? 'localhost' }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">监听端口:</td>
                            <td>{{ $systemInfo['server_config']['port'] ?? '8080' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-memory me-2"></i>缓存统计
                    </h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted">文件缓存数:</td>
                            <td>{{ $systemInfo['cache_stats']['file_cache_count'] ?? 0 }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">缓存大小:</td>
                            <td>{{ formatBytes($systemInfo['cache_stats']['file_cache_size'] ?? 0) }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted">命中率:</td>
                            <td>{{ $systemInfo['cache_stats']['cache_hit_rate'] ?? 0 }}%</td>
                        </tr>
                        <tr>
                            <td class="text-muted">更新时间:</td>
                            <td>{{ now()->format('Y-m-d H:i:s') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- 任务统计 -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-chart-bar me-2"></i>任务统计
                    </h6>
                    <small class="text-muted">最近30天</small>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-primary">{{ $jobStats['total'] }}</h4>
                                <p class="text-muted mb-0">总任务数</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-warning">{{ $jobStats['running'] }}</h4>
                                <p class="text-muted mb-0">运行中</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border-end">
                                <h4 class="text-success">{{ $jobStats['completed'] }}</h4>
                                <p class="text-muted mb-0">已完成</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <h4 class="text-danger">{{ $jobStats['failed'] }}</h4>
                            <p class="text-muted mb-0">失败</p>
                        </div>
                    </div>
                    
                    @if($jobStats['total'] > 0)
                    <div class="mt-3">
                        <div class="progress" style="height: 20px;">
                            @php
                                $completedPercent = round(($jobStats['completed'] / $jobStats['total']) * 100, 1);
                                $runningPercent = round(($jobStats['running'] / $jobStats['total']) * 100, 1);
                                $failedPercent = round(($jobStats['failed'] / $jobStats['total']) * 100, 1);
                            @endphp
                            <div class="progress-bar bg-success" style="width: {{ $completedPercent }}%">
                                {{ $completedPercent }}%
                            </div>
                            <div class="progress-bar bg-warning" style="width: {{ $runningPercent }}%">
                                {{ $runningPercent }}%
                            </div>
                            <div class="progress-bar bg-danger" style="width: {{ $failedPercent }}%">
                                {{ $failedPercent }}%
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 自动刷新页面数据
let autoRefresh = true;
let refreshInterval;

function startAutoRefresh() {
    refreshInterval = setInterval(function() {
        if (autoRefresh) {
            location.reload();
        }
    }, 30000); // 每30秒刷新一次
}

function toggleAutoRefresh() {
    autoRefresh = !autoRefresh;
    const button = document.getElementById('autoRefreshBtn');
    if (button) {
        button.textContent = autoRefresh ? '停止自动刷新' : '开始自动刷新';
        button.className = autoRefresh ? 'btn btn-outline-warning btn-sm' : 'btn btn-outline-success btn-sm';
    }
}

// 启动自动刷新
startAutoRefresh();

// 页面可见性变化时暂停/恢复自动刷新
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        autoRefresh = false;
    } else {
        autoRefresh = true;
    }
});
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
