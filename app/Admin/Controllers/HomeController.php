<?php

namespace App\Admin\Controllers;

use App\Models\SyncJob;
use App\Models\AccessLog;
use App\Http\Controllers\Controller;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Widgets\Card;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->header('PVM 镜像站管理')
            ->description('系统概览')
            ->body(function (Row $row) {
                // 统计卡片
                $row->column(3, function (Column $column) {
                    // 硬编码配置的镜像类型数量
                    $mirrorTypes = ['php', 'pecl', 'github', 'composer'];
                    $mirrorCount = count($mirrorTypes);
                    $activeMirrors = $mirrorCount; // 所有硬编码镜像都是启用的

                    $column->append(new Card('镜像类型', '
                        <div class="text-center">
                            <h2 class="text-primary">' . $mirrorCount . ' 种</h2>
                            <p class="text-muted">' . $activeMirrors . ' 种启用</p>
                            <a href="/admin/sync-jobs" class="btn btn-sm btn-outline-primary">查看同步</a>
                        </div>
                    '));
                });

                $row->column(3, function (Column $column) {
                    $totalJobs = SyncJob::count();
                    $runningJobs = SyncJob::where('status', 'running')->count();

                    $column->append(new Card('同步任务', '
                        <div class="text-center">
                            <h2 class="text-success">' . $totalJobs . ' 个</h2>
                            <p class="text-muted">' . $runningJobs . ' 个运行中</p>
                            <a href="/admin/sync-jobs" class="btn btn-sm btn-outline-success">查看详情</a>
                        </div>
                    '));
                });

                $row->column(3, function (Column $column) {
                    $todayLogs = AccessLog::whereDate('created_at', today())->count();
                    $weekLogs = AccessLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

                    $column->append(new Card('今日访问', '
                        <div class="text-center">
                            <h2 class="text-warning">' . $todayLogs . ' 次</h2>
                            <p class="text-muted">本周 ' . $weekLogs . ' 次</p>
                            <a href="/admin/access-logs" class="btn btn-sm btn-outline-warning">查看详情</a>
                        </div>
                    '));
                });

                $row->column(3, function (Column $column) {
                    $completedJobs = SyncJob::where('status', 'completed')->count();
                    $failedJobs = SyncJob::where('status', 'failed')->count();

                    $column->append(new Card('已完成任务', '
                        <div class="text-center">
                            <h2 class="text-info">' . $completedJobs . ' 个</h2>
                            <p class="text-muted">' . $failedJobs . ' 个失败</p>
                            <a href="/admin/sync-jobs" class="btn btn-sm btn-outline-info">查看详情</a>
                        </div>
                    '));
                });

                // 最近任务和系统信息
                $row->column(6, function (Column $column) {
                    $recentJobs = SyncJob::orderBy('created_at', 'desc')
                        ->limit(5)
                        ->get();

                    $column->append(new Card('最近同步任务', $this->renderRecentJobs($recentJobs)));
                });

                $row->column(6, function (Column $column) {
                    $systemInfo = $this->getSystemInfo();
                    $column->append(new Card('系统信息', $systemInfo));
                });
            });
    }

    private function renderRecentJobs($jobs)
    {
        if ($jobs->isEmpty()) {
            return '<p class="text-muted">暂无同步任务</p>';
        }

        $html = '<div class="table-responsive"><table class="table table-sm">';
        $html .= '<thead><tr><th>镜像</th><th>状态</th><th>时间</th></tr></thead><tbody>';

        foreach ($jobs as $job) {
            $statusClass = [
                'pending' => 'secondary',
                'running' => 'primary',
                'completed' => 'success',
                'failed' => 'danger',
                'cancelled' => 'warning',
            ][$job->status] ?? 'secondary';

            $types = [
                'php' => 'PHP源码',
                'pecl' => 'PECL扩展',
                'github' => 'GitHub扩展',
                'composer' => 'Composer包',
            ];
            $mirrorName = $types[$job->mirror_type] ?? $job->mirror_type;

            $html .= '<tr>';
            $html .= '<td>' . $mirrorName . '</td>';
            $html .= '<td><span class="badge badge-' . $statusClass . '">' . $job->status . '</span></td>';
            $html .= '<td>' . $job->created_at->format('Y-m-d H:i:s') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';
        return $html;
    }

    private function getSystemInfo()
    {
        $totalRequests = AccessLog::count();
        $uniqueIps = AccessLog::distinct('ip')->count();
        $successRate = $totalRequests > 0 ?
            round(AccessLog::where('status_code', 200)->count() / $totalRequests * 100, 1) : 0;
        $avgResponseTime = AccessLog::avg('response_time') ?? 0;
        $totalTraffic = AccessLog::sum('file_size') ?? 0;

        return '
        <table class="table table-sm">
            <tr><td>总请求数:</td><td><strong>' . number_format($totalRequests) . '</strong></td></tr>
            <tr><td>独立IP:</td><td><strong>' . number_format($uniqueIps) . '</strong></td></tr>
            <tr><td>成功率:</td><td><strong>' . $successRate . '%</strong></td></tr>
            <tr><td>平均响应:</td><td><strong>' . round($avgResponseTime) . 'ms</strong></td></tr>
            <tr><td>总流量:</td><td><strong>' . $this->formatBytes($totalTraffic) . '</strong></td></tr>
        </table>';
    }

    private function formatBytes($size, $precision = 2)
    {
        if ($size == 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision) . ' ' . $units[$i];
    }
}
