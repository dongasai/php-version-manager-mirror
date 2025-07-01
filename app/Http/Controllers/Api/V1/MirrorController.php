<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\MirrorResource;
use App\Models\Mirror;
use App\Services\MirrorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MirrorController extends Controller
{
    /**
     * 镜像服务
     *
     * @var MirrorService
     */
    protected $mirrorService;

    /**
     * 构造函数
     *
     * @param MirrorService $mirrorService
     */
    public function __construct(MirrorService $mirrorService)
    {
        $this->mirrorService = $mirrorService;
    }

    /**
     * 获取镜像列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Mirror::query();

        // 筛选条件
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('enabled')) {
            $status = $request->boolean('enabled') ? 1 : 0;
            $query->where('status', $status);
        }

        // 关联数据
        if ($request->has('include')) {
            $includes = explode(',', $request->include);
            if (in_array('latest_sync_job', $includes)) {
                $query->with('latestSyncJob');
            }
            if (in_array('sync_jobs_count', $includes)) {
                $query->withCount('syncJobs');
            }
        }

        // 分页
        $perPage = min($request->get('per_page', 15), 100);
        $mirrors = $query->paginate($perPage);

        // 添加统计信息
        if ($request->boolean('include_stats')) {
            $mirrors->getCollection()->transform(function ($mirror) {
                $mirror->stats = $this->mirrorService->getMirrorStats($mirror);
                return $mirror;
            });
        }

        return response()->json([
            'success' => true,
            'data' => MirrorResource::collection($mirrors),
            'meta' => [
                'current_page' => $mirrors->currentPage(),
                'last_page' => $mirrors->lastPage(),
                'per_page' => $mirrors->perPage(),
                'total' => $mirrors->total(),
            ],
        ]);
    }

    /**
     * 获取指定镜像
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $query = Mirror::where('id', $id);

        // 关联数据
        if ($request->has('include')) {
            $includes = explode(',', $request->include);
            if (in_array('latest_sync_job', $includes)) {
                $query->with('latestSyncJob');
            }
            if (in_array('sync_jobs', $includes)) {
                $query->with(['syncJobs' => function ($q) {
                    $q->orderBy('created_at', 'desc')->limit(10);
                }]);
            }
        }

        $mirror = $query->first();

        if (!$mirror) {
            return response()->json([
                'success' => false,
                'message' => '镜像不存在',
            ], 404);
        }

        // 添加统计信息
        if ($request->boolean('include_stats')) {
            $mirror->stats = $this->mirrorService->getMirrorStats($mirror);
        }

        return response()->json([
            'success' => true,
            'data' => new MirrorResource($mirror),
        ]);
    }

    /**
     * 获取镜像统计信息
     *
     * @param int $id
     * @return JsonResponse
     */
    public function stats(int $id): JsonResponse
    {
        $mirror = Mirror::find($id);

        if (!$mirror) {
            return response()->json([
                'success' => false,
                'message' => '镜像不存在',
            ], 404);
        }

        $stats = $this->mirrorService->getMirrorStats($mirror);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * 同步镜像
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function sync(Request $request, int $id): JsonResponse
    {
        $mirror = Mirror::find($id);

        if (!$mirror) {
            return response()->json([
                'success' => false,
                'message' => '镜像不存在',
            ], 404);
        }

        if (!$mirror->isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => '镜像未启用',
            ], 400);
        }

        try {
            $force = $request->boolean('force', false);
            $syncJob = $this->mirrorService->syncMirror($mirror->id, $force);

            return response()->json([
                'success' => true,
                'message' => '同步任务已创建',
                'data' => [
                    'job_id' => $syncJob->id,
                    'mirror_id' => $mirror->id,
                    'status' => $syncJob->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '同步任务创建失败: ' . $e->getMessage(),
            ], 500);
        }
    }
}
