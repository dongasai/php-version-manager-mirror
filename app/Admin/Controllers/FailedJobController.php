<?php

namespace App\Admin\Controllers;

use App\Models\FailedJob;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * 失败任务管理控制器
 */
class FailedJobController extends AdminController
{
    /**
     * 页面标题
     *
     * @var string
     */
    protected $title = '失败任务管理';

    /**
     * 失败任务列表
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('失败任务管理')
            ->description('管理和重试失败的队列任务')
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
        $stats = FailedJob::getFailedJobStats();
        
        $cards = [
            $this->createStatCard('总失败数', $stats['total'], 'fa-exclamation-triangle', 'danger'),
            $this->createStatCard('今日失败', $stats['today'], 'fa-calendar-day', 'warning'),
            $this->createStatCard('本周失败', $stats['this_week'], 'fa-calendar-week', 'info'),
            $this->createStatCard('本月失败', $stats['this_month'], 'fa-calendar-alt', 'secondary'),
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
     * 失败任务表格
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new FailedJob(), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('uuid', 'UUID')->limit(20);
            $grid->column('connection', '连接')->filter();
            $grid->column('queue', '队列名称')->filter();
            $grid->column('job_class', '任务类型')->display(function ($value) {
                $parts = explode('\\', $value);
                return end($parts);
            });
            $grid->column('exception_summary', '异常摘要')->limit(50);
            $grid->column('formatted_failed_at', '失败时间')->sortable();

            // 筛选器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('connection', '连接')->select(FailedJob::getConnectionNames());
                $filter->equal('queue', '队列名称')->select(FailedJob::getQueueNames());
                $filter->like('exception', '异常信息');
                $filter->between('failed_at', '失败时间')->datetime();
            });

            // 批量操作
            $grid->batchActions([
                new \App\Admin\Actions\Grid\RetryFailedJobAction(),
                new \App\Admin\Actions\Grid\DeleteFailedJobAction(),
            ]);

            // 工具栏
            $grid->tools([
                new \App\Admin\Actions\Grid\RetryAllFailedJobsAction(),
                new \App\Admin\Actions\Grid\ClearFailedJobsAction(),
            ]);

            // 禁用创建按钮
            $grid->disableCreateButton();
            
            // 禁用编辑
            $grid->disableEditButton();
            
            // 设置每页显示数量
            $grid->paginate(20);
            
            // 默认排序
            $grid->model()->orderBy('failed_at', 'desc');
        });
    }

    /**
     * 失败任务详情
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new FailedJob(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('uuid', 'UUID');
            $show->field('connection', '连接');
            $show->field('queue', '队列名称');
            $show->field('job_class', '任务类型');
            $show->field('formatted_failed_at', '失败时间');
            
            $show->field('payload', '任务载荷')->json();
            $show->field('job_data', '任务数据')->json();
            $show->field('exception', '异常信息')->textarea();

            // 禁用编辑和删除按钮
            $show->disableEditButton();
            $show->disableDeleteButton();
        });
    }

    /**
     * 重试失败任务
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function retryJobs(Request $request)
    {
        $ids = $request->get('ids', []);
        
        if (empty($ids)) {
            return response()->json([
                'status' => false,
                'message' => '请选择要重试的任务'
            ]);
        }
        
        try {
            foreach ($ids as $id) {
                Artisan::call('queue:retry', ['id' => $id]);
            }
            
            return response()->json([
                'status' => true,
                'message' => '已重试 ' . count($ids) . ' 个任务'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => '重试任务失败: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 重试所有失败任务
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function retryAllJobs()
    {
        try {
            Artisan::call('queue:retry', ['id' => 'all']);
            
            return response()->json([
                'status' => true,
                'message' => '所有失败任务已加入重试队列'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => '重试所有任务失败: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 清空失败任务
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearFailedJobs()
    {
        try {
            Artisan::call('queue:flush');
            
            return response()->json([
                'status' => true,
                'message' => '所有失败任务已清空'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => '清空失败任务失败: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 删除失败任务
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
            FailedJob::whereIn('id', $ids)->delete();
            
            return response()->json([
                'status' => true,
                'message' => '已删除 ' . count($ids) . ' 个失败任务'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => '删除失败任务失败: ' . $e->getMessage()
            ]);
        }
    }
}
