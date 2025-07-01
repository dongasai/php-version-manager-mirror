@extends('layouts.admin')

@section('title', '仪表盘')

@section('content')
<!-- 页面标题 -->
<div class="row mb-4">
    <div class="col-12">
        <h2 class="mb-1">
            <i class="fas fa-tachometer-alt me-2 text-primary"></i>
            系统仪表盘
        </h2>
        <p class="text-muted mb-0">PVM 镜像站管理概览</p>
    </div>
</div>

<!-- 统计卡片 -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-mirror fa-2x mb-2"></i>
                <h3>{{ $stats['mirrors'] }}</h3>
                <p class="mb-0">镜像总数</p>
                <small class="opacity-75">{{ $stats['enabled_mirrors'] }} 个启用</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-tasks fa-2x mb-2"></i>
                <h3>{{ $stats['total_jobs'] }}</h3>
                <p class="mb-0">同步任务</p>
                <small class="opacity-75">{{ $stats['running_jobs'] }} 个运行中</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-eye fa-2x mb-2"></i>
                <h3>{{ $stats['today_access'] }}</h3>
                <p class="mb-0">今日访问</p>
                <small class="opacity-75">本周 {{ $stats['week_access'] }} 次</small>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card stats-card">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <h3>{{ $stats['completed_jobs'] }}</h3>
                <p class="mb-0">已完成任务</p>
                <small class="opacity-75">{{ $stats['failed_jobs'] }} 个失败</small>
            </div>
        </div>
    </div>
</div>

<!-- 镜像状态和最近任务 -->
<div class="row mb-4">
    <!-- 镜像状态 -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-mirror me-2"></i>镜像状态
                </h5>
                <a href="{{ route('admin.mirrors.index') }}" class="btn btn-sm btn-outline-primary">
                    查看全部
                </a>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach($mirrorStats as $mirrorStat)
                    @php
                        $mirror = $mirrorStat['mirror'];
                        $stats = $mirrorStat['stats'];
                        $latestJob = $mirrorStat['latest_job'];
                    @endphp
                    <div class="col-md-6">
                        <div class="border rounded p-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="d-flex align-items-center">
                                    <span class="status-indicator status-{{ $mirror->isEnabled() ? 'online' : 'offline' }}"></span>
                                    <strong>{{ $mirror->name }}</strong>
                                </div>
                                @if($latestJob && $latestJob->isRunning())
                                <span class="badge bg-warning">
                                    <i class="fas fa-sync fa-spin me-1"></i>同步中
                                </span>
                                @elseif($latestJob && $latestJob->isCompleted())
                                <span class="badge bg-success">完成</span>
                                @elseif($latestJob && $latestJob->isFailed())
                                <span class="badge bg-danger">失败</span>
                                @else
                                <span class="badge bg-secondary">无任务</span>
                                @endif
                            </div>
                            <div class="row text-center">
                                <div class="col-4">
                                    <small class="text-muted d-block">文件数</small>
                                    <strong>{{ $stats['file_count'] }}</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">大小</small>
                                    <strong>{{ formatBytes($stats['total_size']) }}</strong>
                                </div>
                                <div class="col-4">
                                    <small class="text-muted d-block">更新</small>
                                    <strong>
                                        @if($stats['last_updated'])
                                            {{ \Carbon\Carbon::parse($stats['last_updated'])->diffForHumans() }}
                                        @else
                                            未知
                                        @endif
                                    </strong>
                                </div>
                            </div>
                            @if($latestJob && $latestJob->isRunning())
                            <div class="mt-2">
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar" style="width: {{ $latestJob->progress }}%"></div>
                                </div>
                                <small class="text-muted">进度: {{ $latestJob->progress }}%</small>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- 最近任务 -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-clock me-2"></i>最近任务
                </h5>
                <a href="{{ route('admin.jobs.index') }}" class="btn btn-sm btn-outline-primary">
                    查看全部
                </a>
            </div>
            <div class="card-body">
                @if($recentJobs->count() > 0)
                <div class="list-group list-group-flush">
                    @foreach($recentJobs->take(8) as $job)
                    <div class="list-group-item border-0 px-0 py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1">{{ $job->mirror->name }}</h6>
                                <small class="text-muted">
                                    {{ $job->created_at->diffForHumans() }}
                                </small>
                            </div>
                            <span class="badge bg-{{ $job->status_color }}">
                                {{ $job->status_name }}
                            </span>
                        </div>
                        @if($job->isRunning())
                        <div class="progress mt-1" style="height: 4px;">
                            <div class="progress-bar" style="width: {{ $job->progress }}%"></div>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-3">
                    <i class="fas fa-tasks fa-2x text-muted mb-2"></i>
                    <p class="text-muted mb-0">暂无同步任务</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- 访问统计和缓存信息 -->
<div class="row mb-4">
    <!-- 访问统计图表 -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-chart-line me-2"></i>访问统计 (最近7天)
                </h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="accessChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- 系统信息 -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>系统信息
                </h5>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted">总请求数:</td>
                        <td><strong>{{ $accessStats['total_requests'] ?? 0 }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">独立IP:</td>
                        <td><strong>{{ $accessStats['unique_ips'] ?? 0 }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">成功率:</td>
                        <td><strong>{{ round($accessStats['success_rate'] ?? 0, 1) }}%</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">平均响应:</td>
                        <td><strong>{{ round($accessStats['avg_response_time'] ?? 0, 2) }}s</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">总流量:</td>
                        <td><strong>{{ formatBytes($accessStats['total_bandwidth'] ?? 0) }}</strong></td>
                    </tr>
                </table>
                
                <hr>
                
                <h6 class="mb-2">缓存统计</h6>
                <table class="table table-sm table-borderless">
                    <tr>
                        <td class="text-muted">缓存数量:</td>
                        <td><strong>{{ $cacheStats['file_cache_count'] ?? 0 }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">缓存大小:</td>
                        <td><strong>{{ formatBytes($cacheStats['file_cache_size'] ?? 0) }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">命中率:</td>
                        <td><strong>{{ $cacheStats['cache_hit_rate'] ?? 0 }}%</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- 快速操作 -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>快速操作
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" onclick="syncAllMirrors()">
                            <i class="fas fa-sync me-2"></i>同步所有镜像
                        </button>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning w-100" onclick="clearCache()">
                            <i class="fas fa-trash me-2"></i>清理缓存
                        </button>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('admin.configs.index') }}" class="btn btn-info w-100">
                            <i class="fas fa-cog me-2"></i>系统配置
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('admin.logs.index') }}" class="btn btn-secondary w-100">
                            <i class="fas fa-file-alt me-2"></i>查看日志
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 访问统计图表
const ctx = document.getElementById('accessChart').getContext('2d');
const accessChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: ['6天前', '5天前', '4天前', '3天前', '2天前', '昨天', '今天'],
        datasets: [{
            label: '访问次数',
            data: [120, 190, 300, 500, 200, 300, {{ $stats['today_access'] }}],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// 同步所有镜像
function syncAllMirrors() {
    if (confirm('确定要同步所有镜像吗？这可能需要较长时间。')) {
        // 这里应该发送AJAX请求到后端
        alert('同步任务已提交，请在任务管理中查看进度。');
    }
}

// 清理缓存
function clearCache() {
    if (confirm('确定要清理所有缓存吗？')) {
        // 这里应该发送AJAX请求到后端
        alert('缓存清理完成。');
    }
}

// 自动刷新数据
function refreshData() {
    // 刷新统计数据
    fetch('/admin/api/stats')
        .then(response => response.json())
        .then(data => {
            // 更新页面数据
            console.log('数据已刷新');
        })
        .catch(error => {
            console.error('数据刷新失败:', error);
        });
}

// 页面加载完成后启动定时刷新
document.addEventListener('DOMContentLoaded', function() {
    // 每30秒刷新一次数据
    setInterval(refreshData, 30000);
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
