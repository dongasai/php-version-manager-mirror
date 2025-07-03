<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PhpMirrorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PhpController extends Controller
{
    protected PhpMirrorService $phpMirrorService;

    public function __construct(PhpMirrorService $phpMirrorService)
    {
        $this->phpMirrorService = $phpMirrorService;
    }

    /**
     * 获取指定PHP大版本的所有可用版本
     *
     * @param Request $request
     * @param string $majorVersion
     * @return JsonResponse
     */
    public function versions(Request $request, string $majorVersion): JsonResponse
    {
        try {
            // 验证主版本号
            $validator = Validator::make(['major_version' => $majorVersion], [
                'major_version' => 'required|regex:/^[5-8](\.\d+)?$/'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_PARAMETER',
                        'message' => 'Invalid major version format',
                        'details' => $validator->errors()
                    ]
                ], 400);
            }

            // 解析查询参数
            $includePrerelease = $request->boolean('include_prerelease', false);
            $limit = $request->integer('limit', 50);
            $offset = $request->integer('offset', 0);

            // 使用缓存，1小时过期
            $cacheKey = "api.php.versions.{$majorVersion}." . md5(serialize([
                'include_prerelease' => $includePrerelease,
                'limit' => $limit,
                'offset' => $offset
            ]));

            $result = Cache::remember($cacheKey, 3600, function () use ($majorVersion, $includePrerelease, $limit, $offset) {
                return $this->getPhpVersions($majorVersion, $includePrerelease, $limit, $offset);
            });

            return response()->json([
                'success' => true,
                'data' => $result,
                'timestamp' => now()->toISOString(),
                'cache_ttl' => 3600
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SYSTEM_ERROR',
                    'message' => 'Failed to get PHP versions',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }

    /**
     * 获取PECL扩展列表
     *
     * @param Request $request
     * @param string $majorVersion
     * @return JsonResponse
     */
    public function peclExtensions(Request $request, string $majorVersion): JsonResponse
    {
        try {
            // 验证主版本号
            $validator = Validator::make(['major_version' => $majorVersion], [
                'major_version' => 'required|regex:/^[5-8](\.\d+)?$/'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_PARAMETER',
                        'message' => 'Invalid major version format',
                        'details' => $validator->errors()
                    ]
                ], 400);
            }

            // 解析查询参数
            $category = $request->string('category', '');
            $search = $request->string('search', '');
            $limit = $request->integer('limit', 50);
            $offset = $request->integer('offset', 0);

            // 使用缓存，2小时过期
            $cacheKey = "api.php.pecl.{$majorVersion}." . md5(serialize([
                'category' => $category,
                'search' => $search,
                'limit' => $limit,
                'offset' => $offset
            ]));

            $result = Cache::remember($cacheKey, 7200, function () use ($majorVersion, $category, $search, $limit, $offset) {
                return $this->getPeclExtensions($majorVersion, $category, $search, $limit, $offset);
            });

            return response()->json([
                'success' => true,
                'data' => $result,
                'timestamp' => now()->toISOString(),
                'cache_ttl' => 7200
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SYSTEM_ERROR',
                    'message' => 'Failed to get PECL extensions',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }

    /**
     * 获取PHP版本数据
     *
     * @param string $majorVersion
     * @param bool $includePrerelease
     * @param int $limit
     * @param int $offset
     * @return array
     */
    protected function getPhpVersions(string $majorVersion, bool $includePrerelease, int $limit, int $offset): array
    {
        // 模拟数据，实际应该从镜像服务获取
        $allVersions = $this->phpMirrorService->getAvailableVersions($majorVersion, $includePrerelease);

        $total = count($allVersions);
        $versions = array_slice($allVersions, $offset, $limit);

        return [
            'major_version' => $majorVersion,
            'total' => $total,
            'count' => count($versions),
            'offset' => $offset,
            'limit' => $limit,
            'versions' => array_map(function ($version) {
                return [
                    'version' => $version['version'],
                    'release_date' => $version['release_date'],
                    'is_prerelease' => $version['is_prerelease'] ?? false,
                    'download_url' => $version['download_url'],
                    'checksum' => $version['checksum'] ?? null,
                    'size' => $version['size'] ?? null
                ];
            }, $versions)
        ];
    }

    /**
     * 获取PECL扩展数据
     *
     * @param string $majorVersion
     * @param string $category
     * @param string $search
     * @param int $limit
     * @param int $offset
     * @return array
     */
    protected function getPeclExtensions(string $majorVersion, string $category, string $search, int $limit, int $offset): array
    {
        // 模拟数据，实际应该从PECL镜像服务获取
        $allExtensions = $this->phpMirrorService->getPeclExtensions($majorVersion, $category, $search);

        $total = count($allExtensions);
        $extensions = array_slice($allExtensions, $offset, $limit);

        return [
            'major_version' => $majorVersion,
            'category' => $category,
            'search' => $search,
            'total' => $total,
            'count' => count($extensions),
            'offset' => $offset,
            'limit' => $limit,
            'extensions' => array_map(function ($extension) {
                return [
                    'name' => $extension['name'],
                    'summary' => $extension['summary'],
                    'description' => $extension['description'],
                    'category' => $extension['category'],
                    'license' => $extension['license'],
                    'maintainers' => $extension['maintainers'] ?? [],
                    'latest_version' => $extension['latest_version'],
                    'release_date' => $extension['release_date'],
                    'download_count' => $extension['download_count'] ?? 0
                ];
            }, $extensions)
        ];
    }
}
