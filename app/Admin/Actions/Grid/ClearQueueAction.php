<?php

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\Tools\AbstractTool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * 清空队列操作
 */
class ClearQueueAction extends AbstractTool
{
    /**
     * 操作标题
     *
     * @return string
     */
    public function title()
    {
        return '清空队列';
    }

    /**
     * 渲染操作按钮
     *
     * @return string
     */
    public function render()
    {
        $url = admin_url('queue-jobs/clear-queue');
        
        return <<<HTML
<a href="javascript:void(0)" class="btn btn-sm btn-warning" onclick="clearQueue()">
    <i class="fa fa-trash"></i> 清空队列
</a>

<script>
function clearQueue() {
    Dcat.confirm('确定要清空所有队列任务吗？此操作不可恢复！', null, function() {
        $.ajax({
            url: '{$url}',
            type: 'POST',
            data: {
                _token: Dcat.token,
                queue: 'default'
            },
            success: function(response) {
                if (response.status) {
                    Dcat.success(response.message);
                    Dcat.reload();
                } else {
                    Dcat.error(response.message);
                }
            },
            error: function() {
                Dcat.error('操作失败');
            }
        });
    });
}
</script>
HTML;
    }
}
