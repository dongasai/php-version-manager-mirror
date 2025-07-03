<?php

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\BatchAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * 批量重试失败任务操作
 */
class RetryFailedJobAction extends BatchAction
{
    /**
     * 操作标题
     *
     * @return string
     */
    public function title()
    {
        return '重试任务';
    }

    /**
     * 确认信息
     *
     * @return string|array|void
     */
    public function confirm()
    {
        return '确定要重试选中的失败任务吗？';
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
            return $this->response()->error('请选择要重试的任务');
        }

        try {
            $successCount = 0;
            $errorCount = 0;
            
            foreach ($keys as $id) {
                try {
                    Artisan::call('queue:retry', ['id' => $id]);
                    $successCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                }
            }
            
            $message = "重试完成：成功 {$successCount} 个";
            if ($errorCount > 0) {
                $message .= "，失败 {$errorCount} 个";
            }
            
            return $this->response()->success($message)->refresh();
        } catch (\Exception $e) {
            return $this->response()->error('重试失败: ' . $e->getMessage());
        }
    }
}
