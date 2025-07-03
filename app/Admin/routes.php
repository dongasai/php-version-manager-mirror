<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

Admin::routes();

Route::group([
    'prefix'     => config('admin.route.prefix'),
    'namespace'  => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');

    // 队列监控仪表板
    $router->get('queue-dashboard', 'QueueDashboardController@index');

    // 同步任务管理
    $router->resource('sync-jobs', 'SyncJobController');

    // 系统配置管理
    $router->resource('system-configs', 'SystemConfigController');

    // 访问日志
    $router->resource('access-logs', 'AccessLogController');

    // 队列任务管理
    $router->resource('queue-jobs', 'QueueJobController');
    $router->post('queue-jobs/clear-queue', 'QueueJobController@clearQueue');
    $router->post('queue-jobs/restart-queue', 'QueueJobController@restartQueue');
    $router->post('queue-jobs/delete-jobs', 'QueueJobController@deleteJobs');

    // 队列任务执行记录
    $router->resource('job-runs', 'JobRunController')->only(['index', 'show']);

    // 失败任务管理
    $router->resource('failed-jobs', 'FailedJobController');
    $router->post('failed-jobs/retry-jobs', 'FailedJobController@retryJobs');
    $router->post('failed-jobs/retry-all', 'FailedJobController@retryAllJobs');
    $router->post('failed-jobs/clear-all', 'FailedJobController@clearFailedJobs');
    $router->post('failed-jobs/delete-jobs', 'FailedJobController@deleteJobs');

});
