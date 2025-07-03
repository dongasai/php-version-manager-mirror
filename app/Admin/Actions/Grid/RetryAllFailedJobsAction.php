<?php

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\Tools\AbstractTool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * 重试所有失败任务操作
 */
class RetryAllFailedJobsAction extends AbstractTool
{
    /**
     * 操作标题
     *
     * @return string
     */
    public function title()
    {
        return '重试所有';
    }

    /**
     * 渲染操作按钮
     *
     * @return string
     */
    public function render()
    {
        $url = admin_url('failed-jobs/retry-all');
        
        return <<<HTML
<a href="javascript:void(0)" class="btn btn-sm btn-success" onclick="retryAllFailedJobs()">
    <i class="fa fa-refresh"></i> 重试所有
</a>

<script>
function retryAllFailedJobs() {
    Dcat.confirm('确定要重试所有失败任务吗？', null, function() {
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
