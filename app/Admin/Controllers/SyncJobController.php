<?php

namespace App\Admin\Controllers;

use App\Models\SyncJob;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class SyncJobController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new SyncJob(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('mirror_type', '镜像类型')->display(function ($value) {
                $types = [
                    'php' => 'PHP源码',
                    'pecl' => 'PECL扩展',
                    'github' => 'GitHub扩展',
                    'composer' => 'Composer包',
                ];
                return $types[$value] ?? $value;
            })->label([
                'php' => 'primary',
                'pecl' => 'success',
                'github' => 'info',
                'composer' => 'warning',
            ]);
            $grid->column('status', '状态')->using([
                'pending' => '等待中',
                'running' => '运行中',
                'completed' => '已完成',
                'failed' => '失败',
                'cancelled' => '已取消',
            ])->label([
                'pending' => 'default',
                'running' => 'primary',
                'completed' => 'success',
                'failed' => 'danger',
                'cancelled' => 'warning',
            ]);
            $grid->column('progress', '进度')->progressBar();
            $grid->column('started_at', '开始时间')->display(function ($value) {
                return $value ? $value->format('Y-m-d H:i:s') : '-';
            });
            $grid->column('completed_at', '完成时间')->display(function ($value) {
                return $value ? $value->format('Y-m-d H:i:s') : '-';
            });
            $grid->column('created_at', '创建时间')->display(function ($value) {
                return $value ? $value->format('Y-m-d H:i:s') : '-';
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->equal('mirror_type', '镜像类型')->select([
                    'php' => 'PHP源码',
                    'pecl' => 'PECL扩展',
                    'github' => 'GitHub扩展',
                    'composer' => 'Composer包',
                ]);
                $filter->equal('status', '状态')->select([
                    'pending' => '等待中',
                    'running' => '运行中',
                    'completed' => '已完成',
                    'failed' => '失败',
                    'cancelled' => '已取消',
                ]);
                $filter->between('created_at', '创建时间')->datetime();
            });

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $row = $actions->row;
                
                // 根据状态显示不同操作
                if ($row->status === 'failed') {
                    $actions->append('<a href="javascript:void(0)" class="btn btn-sm btn-outline-success retry-job" data-id="'.$actions->getKey().'">重试</a>');
                }
                
                if ($row->status === 'running') {
                    $actions->append('<a href="javascript:void(0)" class="btn btn-sm btn-outline-warning cancel-job" data-id="'.$actions->getKey().'">取消</a>');
                }
            });

            $grid->disableCreateButton();
            $grid->disableEditButton();
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new SyncJob(), function (Show $show) {
            $show->field('id');
            $show->field('mirror_type', '镜像类型')->as(function ($value) {
                $types = [
                    'php' => 'PHP源码',
                    'pecl' => 'PECL扩展',
                    'github' => 'GitHub扩展',
                    'composer' => 'Composer包',
                ];
                return $types[$value] ?? $value;
            });
            $show->field('status', '状态');
            $show->field('progress', '进度')->as(function ($progress) {
                return $progress . '%';
            });
            $show->field('log', '日志信息')->unescape()->as(function ($log) {
                return $log ? '<pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto;">' . htmlspecialchars($log) . '</pre>' : '-';
            });
            $show->field('started_at', '开始时间')->as(function ($value) {
                return $value ? (is_string($value) ? $value : $value->format('Y-m-d H:i:s')) : '-';
            });
            $show->field('completed_at', '完成时间')->as(function ($value) {
                return $value ? (is_string($value) ? $value : $value->format('Y-m-d H:i:s')) : '-';
            });
            $show->field('created_at', '创建时间')->as(function ($value) {
                return $value ? (is_string($value) ? $value : $value->format('Y-m-d H:i:s')) : '-';
            });
            $show->field('updated_at', '更新时间')->as(function ($value) {
                return $value ? (is_string($value) ? $value : $value->format('Y-m-d H:i:s')) : '-';
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new SyncJob(), function (Form $form) {
            $form->display('id');
            $form->select('mirror_type', '镜像类型')->options([
                'php' => 'PHP源码',
                'pecl' => 'PECL扩展',
                'github' => 'GitHub扩展',
                'composer' => 'Composer包',
            ])->required();
            $form->select('status', '状态')->options([
                'pending' => '等待中',
                'running' => '运行中',
                'completed' => '已完成',
                'failed' => '失败',
                'cancelled' => '已取消',
            ])->default('pending');
            $form->number('progress', '进度')->min(0)->max(100)->default(0);
            $form->textarea('log', '日志信息');
            $form->datetime('started_at', '开始时间');
            $form->datetime('completed_at', '完成时间');

            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
        });
    }
}
