<?php

namespace App\Admin\Controllers;

use App\Models\SystemConfig;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class SystemConfigController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new SystemConfig(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('key', '配置键');
            $grid->column('value', '配置值')->limit(50);
            $grid->column('description', '描述')->limit(100);
            $grid->column('type', '类型')->using([
                'string' => '字符串',
                'integer' => '整数',
                'boolean' => '布尔值',
                'json' => 'JSON',
                'text' => '文本',
            ])->label([
                'string' => 'primary',
                'integer' => 'success',
                'boolean' => 'info',
                'json' => 'warning',
                'text' => 'default',
            ]);
            $grid->column('created_at', '创建时间');
            $grid->column('updated_at', '更新时间');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->like('key', '配置键');
                $filter->like('description', '描述');
                $filter->equal('type', '类型')->select([
                    'string' => '字符串',
                    'integer' => '整数',
                    'boolean' => '布尔值',
                    'json' => 'JSON',
                    'text' => '文本',
                ]);
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
        return Show::make($id, new SystemConfig(), function (Show $show) {
            $show->field('id');
            $show->field('key', '配置键');
            $show->field('value', '配置值');
            $show->field('description', '描述');
            $show->field('type', '类型');
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
        return Form::make(new SystemConfig(), function (Form $form) {
            $form->display('id');
            $form->text('key', '配置键')->required()->help('配置项的唯一标识符');
            
            $form->select('type', '类型')->options([
                'string' => '字符串',
                'integer' => '整数',
                'boolean' => '布尔值',
                'json' => 'JSON',
                'text' => '文本',
            ])->required()->when('text', function (Form $form) {
                $form->textarea('value', '配置值');
            })->when('json', function (Form $form) {
                $form->textarea('value', '配置值')->help('请输入有效的JSON格式');
            })->when('boolean', function (Form $form) {
                $form->switch('value', '配置值');
            })->when('integer', function (Form $form) {
                $form->number('value', '配置值');
            })->when('string', function (Form $form) {
                $form->text('value', '配置值');
            });
            
            $form->text('description', '描述')->help('配置项的说明');

            $form->display('created_at', '创建时间');
            $form->display('updated_at', '更新时间');
        });
    }
}
