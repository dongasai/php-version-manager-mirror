<?php

namespace App\Admin\Controllers;

use App\Models\AccessLog;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class AccessLogController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new AccessLog(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('ip', 'IP地址');
            $grid->column('method', '请求方法')->label([
                'GET' => 'success',
                'POST' => 'primary',
                'PUT' => 'warning',
                'DELETE' => 'danger',
            ]);
            $grid->column('path', '请求路径')->limit(50);
            $grid->column('status_code', '状态码')->using([
                200 => '200 OK',
                404 => '404 Not Found',
                500 => '500 Server Error',
            ])->label([
                200 => 'success',
                404 => 'warning',
                500 => 'danger',
            ]);
            $grid->column('response_time', '响应时间')->suffix('ms');
            $grid->column('file_size', '文件大小')->display(function ($size) {
                if ($size === null) return '-';
                return $this->formatBytes($size);
            });
            $grid->column('user_agent', '用户代理')->limit(30);
            $grid->column('created_at', '访问时间');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->like('ip', 'IP地址');
                $filter->equal('method', '请求方法')->select([
                    'GET' => 'GET',
                    'POST' => 'POST',
                    'PUT' => 'PUT',
                    'DELETE' => 'DELETE',
                ]);
                $filter->like('path', '请求路径');
                $filter->equal('status_code', '状态码')->select([
                    200 => '200 OK',
                    404 => '404 Not Found',
                    500 => '500 Server Error',
                ]);
                $filter->between('created_at', '访问时间')->datetime();
            });

            $grid->disableCreateButton();
            $grid->disableEditButton();
            $grid->disableDeleteButton();
            
            // 默认按时间倒序
            $grid->model()->orderBy('created_at', 'desc');
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
        return Show::make($id, new AccessLog(), function (Show $show) {
            $show->field('id');
            $show->field('ip', 'IP地址');
            $show->field('method', '请求方法');
            $show->field('path', '请求路径');
            $show->field('query_string', '查询参数');
            $show->field('status_code', '状态码');
            $show->field('response_time', '响应时间')->as(function ($time) {
                return $time . 'ms';
            });
            $show->field('file_size', '文件大小')->as(function ($size) {
                if ($size === null) return '-';
                return $this->formatBytes($size);
            });
            $show->field('referer', '来源页面');
            $show->field('user_agent', '用户代理');
            $show->field('created_at', '访问时间');
        });
    }

    /**
     * 格式化字节大小
     */
    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }
}
