<?php

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\Tools\AbstractTool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * 清空所有失败任务操作
 */
class ClearFailedJobsAction extends AbstractTool
{
    /**
     * 操作标题
     *
     * @return string
     */
    public function title()
    {
        return '清空所有';
    }

    /**
     * 渲染操作按钮
     *
     * @return string
     */
    public function render()
    {
        $url = admin_url('failed-jobs/clear-all');
        
        return <<<HTML
<a href="javascript:void(0)" class="btn btn-sm btn-danger" onclick="clearAllFailedJobs()">
    <i class="fa fa-trash"></i> 清空所有
</a>

<script>
function clearAllFailedJobs() {
    Dcat.confirm('确定要清空所有失败任务吗？此操作不可恢复！', null, function() {
        $.ajax({
            url: '{$url}',
            type: 'POST',
            data: {
                _token: Dcat.token
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
