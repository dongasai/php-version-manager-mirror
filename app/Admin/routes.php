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

    // 镜像管理
    $router->resource('mirrors', 'MirrorController');

    // 同步任务管理
    $router->resource('sync-jobs', 'SyncJobController');

    // 系统配置管理
    $router->resource('system-configs', 'SystemConfigController');

    // 访问日志
    $router->resource('access-logs', 'AccessLogController');

});
