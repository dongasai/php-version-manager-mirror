<?php

namespace App\Admin\Controllers;

use App\Models\JobRun;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Layout\Content;

/**
 * 队列任务执行记录管理控制器
 */
class JobRunController extends AdminController
{
    /**
     * 列表页面
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->title('队列任务执行记录')
            ->description('查看队列任务的执行记录和统计信息')
            ->body($this->grid());
    }

    /**
     * 详情页面
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->title('任务执行详情')
            ->description('查看任务执行的详细信息')
            ->body($this->detail($id));
    }

    /**
     * 构建数据表格
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(JobRun::with(['job']), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('job_class', '任务类型')->display(function ($value) {
                return class_basename($value);
            })->label('primary');

            $grid->column('queue', '队列')->label('info');

            $grid->column('status', '状态')->display(function ($value) {
                $colors = [
                    JobRun::STATUS_RUNNING => 'warning',
                    JobRun::STATUS_COMPLETED => 'success',
                    JobRun::STATUS_FAILED => 'danger',
                    JobRun::STATUS_TIMEOUT => 'secondary',
                ];

                $statusOptions = JobRun::getStatusOptions();
                $label = $statusOptions[$value] ?? $value;

                return "<span class='label label-{$colors[$value]}'>{$label}</span>";
            });

            $grid->column('execution_time', '执行时间')->display(function ($value) {
                return $value ? number_format($value, 3) . 's' : '-';
            })->sortable();

            $grid->column('memory_usage', '内存使用')->display(function ($value) {
                if (!$value) {
                    return '-';
                }

                $units = ['B', 'KB', 'MB', 'GB'];
                $bytes = $value;
                $i = 0;

                while ($bytes >= 1024 && $i < count($units) - 1) {
                    $bytes /= 1024;
                    $i++;
                }

                return round($bytes, 2) . ' ' . $units[$i];
            });

            $grid->column('started_at', '开始时间')->sortable();
            $grid->column('completed_at', '完成时间')->sortable();

            // 筛选器
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('job_class', '任务类型')->select([
                    'App\\Jobs\\SyncMirrorJob' => '镜像同步任务',
                ]);

                $filter->equal('queue', '队列')->select([
                    'default' => 'default',
                    'sync' => 'sync',
                ]);

                $filter->equal('status', '状态')->select(JobRun::getStatusOptions());

                $filter->between('started_at', '开始时间')->datetime();
                $filter->between('execution_time', '执行时间(秒)');
            });

            // 工具栏
            $grid->tools(function (Grid\Tools $tools) {
                $tools->batch(function (Grid\Tools\BatchActions $actions) {
                    $actions->disableDelete();
                });
            });

            // 禁用操作
            $grid->disableCreateButton();
            $grid->disableEditButton();
            $grid->disableDeleteButton();

            // 排序
            $grid->model()->orderBy('id', 'desc');

            // 分页
            $grid->paginate(20);
        });
    }

    /**
     * 构建详情页面
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, JobRun::with(['job']), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('job_id', '队列任务ID');
            $show->field('job_class', '任务类型');
            $show->field('queue', '队列');
            $show->field('status_label', '状态');

            $show->field('payload', '任务载荷')->json();
            $show->field('output', '执行输出')->textarea();
            $show->field('error', '错误信息')->textarea();

            $show->field('formatted_memory_usage', '内存使用');
            $show->field('execution_time', '执行时间')->as(function ($value) {
                return $value ? number_format($value, 3) . ' 秒' : '-';
            });

            $show->field('started_at', '开始时间');
            $show->field('completed_at', '完成时间');
            $show->field('duration', '持续时间')->as(function ($value) {
                return $value ? number_format($value, 1) . ' 秒' : '-';
            });

            $show->field('created_at', '创建时间');
            $show->field('updated_at', '更新时间');

            // 禁用操作
            $show->disableEditButton();
            $show->disableDeleteButton();
        });
    }
}
