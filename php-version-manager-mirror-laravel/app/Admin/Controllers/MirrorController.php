<?php

namespace App\Admin\Controllers;

use App\Models\Mirror;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class MirrorController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Mirror(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name', '镜像名称');
            $grid->column('type', '镜像类型')->using([
                'php' => 'PHP源码',
                'pecl' => 'PECL扩展',
                'extension' => 'GitHub扩展',
                'composer' => 'Composer',
            ])->label([
                'php' => 'primary',
                'pecl' => 'success',
                'extension' => 'info',
                'composer' => 'warning',
            ]);
            $grid->column('url', '镜像URL')->limit(50);
            $grid->column('status', '状态')->switch();
            $grid->column('created_at', '创建时间');
            $grid->column('updated_at', '更新时间');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->like('name', '镜像名称');
                $filter->equal('type', '镜像类型')->select([
                    'php' => 'PHP源码',
                    'pecl' => 'PECL扩展',
                    'extension' => 'GitHub扩展',
                    'composer' => 'Composer',
                ]);
                $filter->equal('status', '状态')->select([
                    1 => '启用',
                    0 => '禁用',
                ]);
            });

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                // 添加同步按钮
                $actions->append('<a href="javascript:void(0)" class="btn btn-sm btn-outline-primary sync-mirror" data-id="'.$actions->getKey().'">同步</a>');
            });
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
        return Show::make($id, new Mirror(), function (Show $show) {
            $show->field('id');
            $show->field('name', '镜像名称');
            $show->field('type', '镜像类型');
            $show->field('url', '镜像URL');
            $show->field('status', '状态')->using([1 => '启用', 0 => '禁用']);
            $show->field('config', '配置信息')->json();
            $show->field('created_at', '创建时间');
            $show->field('updated_at', '更新时间');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Mirror(), function (Form $form) {
            $form->display('id');
            $form->text('name', '镜像名称')->required();
            $form->select('type', '镜像类型')->options([
                'php' => 'PHP源码',
                'pecl' => 'PECL扩展',
                'extension' => 'GitHub扩展',
                'composer' => 'Composer',
            ])->required();
            $form->url('url', '镜像URL')->required();
            $form->switch('status', '状态')->default(1);
            $form->textarea('config', '配置信息')->help('JSON格式的配置信息');

            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
        });
    }
}
