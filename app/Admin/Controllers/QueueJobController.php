<?php

namespace App\Admin\Controllers;

use App\Models\Job;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Box;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * 队列任务管理控制器
 */
class QueueJobController extends AdminController
{
    /**
     * 页面标题
     *
     * @var string
     */
    protected $title = '队列任务管理';

    /**
     * 队列任务列表
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('队列任务管理')
            ->description('管理和监控队列任务')
            ->body($this->statsCards())
            ->body($this->grid());
    }

    /**
     * 统计卡片
     *
     * @return string
     */
    protected function statsCards()
    {
        $stats = Job::getQueueStats();
        
        $cards = [
            $this->createStatCard('总任务数', $stats['total'], 'fa-tasks', 'primary'),
            $this->createStatCard('处理中', $stats['processing'], 'fa-spinner', 'warning'),
            $this->createStatCard('等待中', $stats['pending'], 'fa-clock', 'info'),
            $this->createStatCard('延迟任务', $stats['delayed'], 'fa-pause', 'secondary'),
        ];

        return '<div class="row">' . implode('', $cards) . '</div>';
    }

    /**
     * 创建统计卡片
     *
     * @param string $title
     * @param int $count
     * @param string $icon
     * @param string $color
     * @return string
     */
    protected function createStatCard($title, $count, $icon, $color)
    {
        return '<div class="col-lg-3 col-6">
            <div class="small-box bg-' . $color . '">
                <div class="inner">
                    <h3>' . $count . '</h3>
                    <p>' . $title . '</p>
                </div>
                <div class="icon">
                    <i class="fa ' . $icon . '"></i>
                </div>
            </div>
        </div>';
    }

    /**
     * 队列任务表格
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Job(), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('queue', '队列名称')->filter();
            $grid->column('job_class', '任务类型')->display(function ($value) {
                $parts = explode('\\', $value);
                return end($parts);
            });
            $grid->column('attempts', '尝试次数')->sortable();
            $grid->column('status_label', '状态')->display(function ($value) {
                return $value;
            });
            $grid->column('created_at', '创建时间')->display(function ($value) {
                return $value ? date('Y-m-d H:i:s', $value) : '';
            })->sortable();
            $grid->column('available_at', '可用时间')->display(function ($value) {
                return $value ? date('Y-m-d H:i:s', $value) : '';
            })->sortable();
            $grid->column('reserved_at', '保留时间')->display(function ($value) {
                return $value ? date('Y-m-d H:i:s', $value) : '';
            });

            // 筛选器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('queue', '队列名称')->select(Job::getQueueNames());
                $filter->equal('attempts', '尝试次数');
                $filter->where('status', function ($query) {
                    $query->status($this->input);
                }, '状态')->select([
                    'pending' => '等待中',
                    'processing' => '处理中',
                    'delayed' => '延迟',
                ]);
            });

            // 批量操作
            $grid->batchActions([
                new \App\Admin\Actions\Grid\DeleteQueueJobAction(),
            ]);

            // 工具栏
            $grid->tools([
                new \App\Admin\Actions\Grid\ClearQueueAction(),
                new \App\Admin\Actions\Grid\RestartQueueAction(),
            ]);

            // 禁用创建按钮
            $grid->disableCreateButton();
            
            // 禁用编辑
            $grid->disableEditButton();
            
            // 设置每页显示数量
            $grid->paginate(20);
            
            // 默认排序
            $grid->model()->orderBy('id', 'desc');
        });
    }

    /**
     * 任务详情
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new Job(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('queue', '队列名称');
            $show->field('job_class', '任务类型')->as(function ($value) {
                $job = Job::find(request()->route('queue_job'));
                return $job->job_class;
            });
            $show->field('attempts', '尝试次数');
            $show->field('status', '状态')->as(function ($value) {
                $job = Job::find(request()->route('queue_job'));
                $status = $job->status;
                $statusMap = [
                    'pending' => '等待中',
                    'processing' => '处理中',
                    'delayed' => '延迟',
                ];
                return $statusMap[$status] ?? $status;
            });
            $show->field('created_at', '创建时间')->as(function ($value) {
                return $value ? date('Y-m-d H:i:s', $value) : '';
            });
            $show->field('available_at', '可用时间')->as(function ($value) {
                return $value ? date('Y-m-d H:i:s', $value) : '';
            });
            $show->field('reserved_at', '保留时间')->as(function ($value) {
                return $value ? date('Y-m-d H:i:s', $value) : '-';
            });

            $show->field('payload', '任务载荷')->as(function ($value) {
                $payload = json_decode($value, true);
                if (is_string($payload)) {
                    $payload = json_decode($payload, true);
                }
                return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            });

            $show->field('job_data', '任务数据')->as(function ($value) {
                // 通过模型实例获取job_data
                $job = Job::find(request()->route('queue_job'));
                return json_encode($job->job_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            });

            // 禁用编辑和删除按钮
            $show->disableEditButton();
            $show->disableDeleteButton();
        });
    }

    /**
     * 清空队列
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearQueue(Request $request)
    {
        $queue = $request->get('queue', 'default');
        
        try {
            Artisan::call('queue:clear', ['connection' => 'database', '--queue' => $queue]);
            
            return response()->json([
                'status' => true,
                'message' => "队列 {$queue} 已清空"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => '清空队列失败: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 重启队列工作进程
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function restartQueue()
    {
        try {
            Artisan::call('queue:restart');
            
            return response()->json([
                'status' => true,
                'message' => '队列工作进程重启信号已发送'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => '重启队列失败: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 删除任务
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteJobs(Request $request)
    {
        $ids = $request->get('ids', []);
        
        if (empty($ids)) {
            return response()->json([
                'status' => false,
                'message' => '请选择要删除的任务'
            ]);
        }
        
        try {
            Job::whereIn('id', $ids)->delete();
            
            return response()->json([
                'status' => true,
                'message' => '已删除 ' . count($ids) . ' 个任务'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => '删除任务失败: ' . $e->getMessage()
            ]);
        }
    }
}
