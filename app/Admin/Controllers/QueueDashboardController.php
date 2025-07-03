<?php

namespace App\Admin\Controllers;

use App\Models\Job;
use App\Models\FailedJob;
use App\Models\SyncJob;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Box;
use Illuminate\Http\Request;

/**
 * 队列监控仪表板控制器
 */
class QueueDashboardController extends AdminController
{
    /**
     * 页面标题
     *
     * @var string
     */
    protected $title = '队列监控仪表板';

    /**
     * 队列监控仪表板
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('队列监控仪表板')
            ->description('实时监控队列状态和性能')
            ->body($this->overviewCards())
            ->body($this->queueCharts())
            ->body($this->recentJobs());
    }

    /**
     * 概览卡片
     *
     * @return string
     */
    protected function overviewCards()
    {
        $queueStats = Job::getQueueStats();
        $failedStats = FailedJob::getFailedJobStats();
        $syncStats = SyncJob::getSyncJobStats();
        
        $cards = [
            $this->createStatCard('队列任务', $queueStats['total'], 'fa-tasks', 'primary', [
                '等待中' => $queueStats['pending'],
                '处理中' => $queueStats['processing'],
                '延迟' => $queueStats['delayed'],
            ]),
            $this->createStatCard('失败任务', $failedStats['total'], 'fa-exclamation-triangle', 'danger', [
                '今日' => $failedStats['today'],
                '本周' => $failedStats['this_week'],
                '本月' => $failedStats['this_month'],
            ]),
            $this->createStatCard('同步任务', $syncStats['total'], 'fa-sync', 'info', [
                '运行中' => $syncStats['running'],
                '已完成' => $syncStats['completed'],
                '失败' => $syncStats['failed'],
            ]),
            $this->createStatCard('系统状态', '正常', 'fa-heartbeat', 'success', [
                '队列工作进程' => '运行中',
                '内存使用' => '正常',
                '磁盘空间' => '充足',
            ]),
        ];

        return '<div class="row">' . implode('', $cards) . '</div>';
    }

    /**
     * 创建统计卡片
     *
     * @param string $title
     * @param mixed $count
     * @param string $icon
     * @param string $color
     * @param array $details
     * @return string
     */
    protected function createStatCard($title, $count, $icon, $color, $details = [])
    {
        $detailsHtml = '';
        if (!empty($details)) {
            $detailsHtml = '<div class="mt-2">';
            foreach ($details as $key => $value) {
                $detailsHtml .= '<small class="d-block">' . $key . ': ' . $value . '</small>';
            }
            $detailsHtml .= '</div>';
        }

        return '<div class="col-lg-3 col-6">
            <div class="small-box bg-' . $color . '">
                <div class="inner">
                    <h3>' . $count . '</h3>
                    <p>' . $title . '</p>
                    ' . $detailsHtml . '
                </div>
                <div class="icon">
                    <i class="fa ' . $icon . '"></i>
                </div>
            </div>
        </div>';
    }

    /**
     * 队列图表
     *
     * @return string
     */
    protected function queueCharts()
    {
        $failedByDate = FailedJob::getFailedJobsByDate(7);
        $failedByQueue = FailedJob::getFailedJobsByQueue();
        
        // 准备图表数据
        $dateLabels = array_keys($failedByDate);
        $dateData = array_values($failedByDate);
        
        $queueLabels = array_keys($failedByQueue);
        $queueData = array_values($failedByQueue);

        return '<div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">近7天失败任务趋势</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="failedJobsChart" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">队列失败任务分布</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="queueDistributionChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // 失败任务趋势图
        var ctx1 = document.getElementById("failedJobsChart").getContext("2d");
        new Chart(ctx1, {
            type: "line",
            data: {
                labels: ' . json_encode($dateLabels) . ',
                datasets: [{
                    label: "失败任务数",
                    data: ' . json_encode($dateData) . ',
                    borderColor: "rgb(255, 99, 132)",
                    backgroundColor: "rgba(255, 99, 132, 0.2)",
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // 队列分布图
        var ctx2 = document.getElementById("queueDistributionChart").getContext("2d");
        new Chart(ctx2, {
            type: "doughnut",
            data: {
                labels: ' . json_encode($queueLabels) . ',
                datasets: [{
                    data: ' . json_encode($queueData) . ',
                    backgroundColor: [
                        "#FF6384",
                        "#36A2EB",
                        "#FFCE56",
                        "#4BC0C0",
                        "#9966FF"
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        </script>';
    }

    /**
     * 最近任务
     *
     * @return string
     */
    protected function recentJobs()
    {
        $recentJobs = Job::orderBy('id', 'desc')->limit(10)->get();
        $recentFailedJobs = FailedJob::orderBy('failed_at', 'desc')->limit(10)->get();

        $jobsHtml = '';
        foreach ($recentJobs as $job) {
            $jobsHtml .= '<tr>
                <td>' . $job->id . '</td>
                <td>' . $job->queue . '</td>
                <td>' . class_basename($job->job_class) . '</td>
                <td>' . $job->status_label . '</td>
                <td>' . $job->formatted_created_at . '</td>
            </tr>';
        }

        $failedJobsHtml = '';
        foreach ($recentFailedJobs as $job) {
            $failedJobsHtml .= '<tr>
                <td>' . $job->id . '</td>
                <td>' . $job->queue . '</td>
                <td>' . class_basename($job->job_class) . '</td>
                <td>' . $job->exception_summary . '</td>
                <td>' . $job->formatted_failed_at . '</td>
            </tr>';
        }

        return '<div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">最近队列任务</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>队列</th>
                                    <th>任务类型</th>
                                    <th>状态</th>
                                    <th>创建时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                ' . $jobsHtml . '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">最近失败任务</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>队列</th>
                                    <th>任务类型</th>
                                    <th>异常</th>
                                    <th>失败时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                ' . $failedJobsHtml . '
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>';
    }
}
