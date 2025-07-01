<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SyncJobResource;
use App\Models\SyncJob;
use App\Services\MirrorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SyncJobController extends Controller
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
     * 获取同步任务列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = SyncJob::query();

        // 筛选条件
        if ($request->has('mirror_id')) {
            $query->where('mirror_id', $request->mirror_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('running')) {
            if ($request->boolean('running')) {
                $query->running();
            }
        }

        // 关联数据
        if ($request->has('include')) {
            $includes = explode(',', $request->include);
            if (in_array('mirror', $includes)) {
                $query->with('mirror');
            }
        }

        // 排序
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // 分页
        $perPage = min($request->get('per_page', 15), 100);
        $jobs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => SyncJobResource::collection($jobs),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
            ],
        ]);
    }

    /**
     * 创建同步任务
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'mirror_id' => 'required|exists:mirrors,id',
            'force' => 'boolean',
        ]);

        try {
            $syncJob = $this->mirrorService->syncMirror(
                $request->mirror_id,
                $request->boolean('force', false)
            );

            return response()->json([
                'success' => true,
                'message' => '同步任务已创建',
                'data' => new SyncJobResource($syncJob),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '同步任务创建失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取指定同步任务
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $query = SyncJob::where('id', $id);

        // 关联数据
        if ($request->has('include')) {
            $includes = explode(',', $request->include);
            if (in_array('mirror', $includes)) {
                $query->with('mirror');
            }
        }

        $job = $query->first();

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => '同步任务不存在',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new SyncJobResource($job),
        ]);
    }

    /**
     * 取消同步任务
     *
     * @param int $id
     * @return JsonResponse
     */
    public function cancel(int $id): JsonResponse
    {
        $job = SyncJob::find($id);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => '同步任务不存在',
            ], 404);
        }

        if (!$job->canCancel()) {
            return response()->json([
                'success' => false,
                'message' => '任务无法取消',
            ], 400);
        }

        try {
            $job->markAsCancelled();

            return response()->json([
                'success' => true,
                'message' => '任务已取消',
                'data' => new SyncJobResource($job),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '任务取消失败: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 获取任务统计
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $days = $request->get('days', 7);

        $stats = [
            'total' => SyncJob::count(),
            'running' => SyncJob::running()->count(),
            'completed' => SyncJob::completed()->count(),
            'failed' => SyncJob::failed()->count(),
            'recent' => SyncJob::where('created_at', '>=', now()->subDays($days))->count(),
        ];

        // 按状态分组统计
        $statusStats = SyncJob::selectRaw('status, COUNT(*) as count')
                             ->groupBy('status')
                             ->pluck('count', 'status')
                             ->toArray();

        // 按镜像分组统计
        $mirrorStats = SyncJob::with('mirror')
                             ->selectRaw('mirror_id, COUNT(*) as count')
                             ->groupBy('mirror_id')
                             ->get()
                             ->map(function ($item) {
                                 return [
                                     'mirror_id' => $item->mirror_id,
                                     'mirror_name' => $item->mirror->name ?? 'Unknown',
                                     'count' => $item->count,
                                 ];
                             });

        return response()->json([
            'success' => true,
            'data' => [
                'overview' => $stats,
                'by_status' => $statusStats,
                'by_mirror' => $mirrorStats,
            ],
        ]);
    }
}
