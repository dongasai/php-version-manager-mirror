<?php

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\BatchAction;
use Illuminate\Http\Request;

/**
 * 批量删除队列任务操作
 */
class DeleteQueueJobAction extends BatchAction
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
        return '确定要删除选中的队列任务吗？';
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
            // 删除选中的任务
            $this->getModel()::whereIn($this->getKeyName(), $keys)->delete();
            
            return $this->response()->success('已删除 ' . count($keys) . ' 个任务')->refresh();
        } catch (\Exception $e) {
            return $this->response()->error('删除失败: ' . $e->getMessage());
        }
    }
}
