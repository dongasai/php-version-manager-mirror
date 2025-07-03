<?php

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\BatchAction;
use Illuminate\Http\Request;

/**
 * 批量删除失败任务操作
 */
class DeleteFailedJobAction extends BatchAction
{
    /**
     * 操作标题
     *
     * @return string
     */
    public function title()
    {
        return '删除任务';
    }

    /**
     * 确认信息
     *
     * @return string|array|void
     */
    public function confirm()
    {
        return '确定要删除选中的失败任务吗？';
    }

    /**
     * 处理请求
     *
     * @param Request $request
     * @return Response
     */
    public function handle(Request $request)
    {
        $keys = $this->getKey();
        
        if (empty($keys)) {
            return $this->response()->error('请选择要删除的任务');
        }

        try {
            // 删除选中的失败任务
            $this->getModel()::whereIn($this->getKeyName(), $keys)->delete();
            
            return $this->response()->success('已删除 ' . count($keys) . ' 个失败任务')->refresh();
        } catch (\Exception $e) {
            return $this->response()->error('删除失败: ' . $e->getMessage());
        }
    }
}
