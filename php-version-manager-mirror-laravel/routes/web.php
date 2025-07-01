<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

use App\Http\Controllers\HomeController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\Api\ApiController;

// 首页
Route::get('/', [HomeController::class, 'index'])->name('home');

// 状态页面
Route::get('/status', [StatusController::class, 'index'])->name('status');

// 文档页面
Route::get('/docs', [DocsController::class, 'index'])->name('docs');

// Ping测速端点
Route::get('/ping', [StatusController::class, 'ping'])->name('ping');

// API路由
Route::prefix('api')->group(function () {
    Route::get('/status', [ApiController::class, 'status'])->name('api.status');
    Route::get('/php', [ApiController::class, 'php'])->name('api.php');
    Route::get('/pecl', [ApiController::class, 'pecl'])->name('api.pecl');
    Route::get('/extensions', [ApiController::class, 'extensions'])->name('api.extensions');
    Route::get('/composer', [ApiController::class, 'composer'])->name('api.composer');
});

// 管理后台路由由 Dcat Admin 自动处理

// 文件下载和目录浏览 (通配符路由，放在最后)
Route::get('/{path}', [FileController::class, 'handle'])
    ->where('path', '.*')
    ->name('file.handle');
