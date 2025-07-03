<?php

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\Tools\AbstractTool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * 重启队列操作
 */
class RestartQueueAction extends AbstractTool
{
    /**
     * 操作标题
     *
     * @return string
     */
    public function title()
    {
        return '重启队列';
    }

    /**
     * 渲染操作按钮
     *
     * @return string
     */
    public function render()
    {
        $url = admin_url('queue-jobs/restart-queue');
        
        return <<<HTML
<a href="javascript:void(0)" class="btn btn-sm btn-info" onclick="restartQueue()">
    <i class="fa fa-refresh"></i> 重启队列
</a>

<script>
function restartQueue() {
    Dcat.confirm('确定要重启队列工作进程吗？', null, function() {
        $.ajax({
            url: '{$url}',
            type: 'POST',
            data: {
                _token: Dcat.token
            },
            success: function(response) {
                if (response.status) {
                    Dcat.success(response.message);
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
