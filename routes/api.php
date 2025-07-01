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

use App\Http\Controllers\Api\StatusController;
use App\Http\Controllers\Api\PhpController;
use App\Http\Controllers\Api\PeclController;
use App\Http\Controllers\Api\ComposerController;
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
    Route::get('status', [StatusController::class, 'index'])->name('status');
    Route::get('ping', function () {
        return response()->json([
            'success' => true,
            'message' => 'pong',
            'timestamp' => time(),
            'datetime' => now()->toISOString(),
        ]);
    })->name('ping');
});

// 核心API接口 (无版本前缀，保持向后兼容)
Route::get('status', [StatusController::class, 'index'])->name('status');
Route::get('php/version/{major_version}', [PhpController::class, 'versions'])->name('php.versions');
Route::get('php/pecl/{major_version}', [PhpController::class, 'peclExtensions'])->name('php.pecl');
Route::get('pecl/{extension_name}', [PeclController::class, 'versions'])->name('pecl.versions');
Route::get('pecl/{extension_name}/info', [PeclController::class, 'show'])->name('pecl.info');
Route::get('composer', [ComposerController::class, 'versions'])->name('composer.versions');
Route::get('composer/latest', [ComposerController::class, 'latest'])->name('composer.latest');
Route::get('composer/installer', [ComposerController::class, 'installer'])->name('composer.installer');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
