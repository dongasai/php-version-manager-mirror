<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\V1\MirrorController;
use App\Http\Controllers\Api\V1\SyncJobController;

// API v1 路由
Route::prefix('v1')->name('v1.')->group(function () {
    // 镜像管理
    Route::apiResource('mirrors', MirrorController::class);
    Route::post('mirrors/{mirror}/sync', [MirrorController::class, 'sync'])->name('mirrors.sync');
    Route::get('mirrors/{mirror}/stats', [MirrorController::class, 'stats'])->name('mirrors.stats');

    // 同步任务管理
    Route::apiResource('sync-jobs', SyncJobController::class);
    Route::post('sync-jobs/{job}/cancel', [SyncJobController::class, 'cancel'])->name('sync-jobs.cancel');
    Route::get('sync-jobs-stats', [SyncJobController::class, 'stats'])->name('sync-jobs.stats');

    // 系统状态
    Route::get('status', [ApiController::class, 'status'])->name('status');
    Route::get('ping', function () {
        return response()->json([
            'success' => true,
            'message' => 'pong',
            'timestamp' => time(),
            'datetime' => now()->toISOString(),
        ]);
    })->name('ping');
});

// 兼容旧版API (无版本前缀)
Route::get('status', [ApiController::class, 'status']);
Route::get('php', [ApiController::class, 'php']);
Route::get('pecl', [ApiController::class, 'pecl']);
Route::get('extensions', [ApiController::class, 'extensions']);
Route::get('composer', [ApiController::class, 'composer']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
