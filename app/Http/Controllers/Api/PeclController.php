<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PeclMirrorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PeclController extends Controller
{
    protected PeclMirrorService $peclMirrorService;

    public function __construct(PeclMirrorService $peclMirrorService)
    {
        $this->peclMirrorService = $peclMirrorService;
    }

    /**
     * 获取指定PECL扩展的所有可用版本
     *
     * @param Request $request
     * @param string $extensionName
     * @return JsonResponse
     */
    public function versions(Request $request, string $extensionName): JsonResponse
    {
        try {
            // 验证扩展名
            $validator = Validator::make(['extension_name' => $extensionName], [
                'extension_name' => 'required|string|min:1|max:50|regex:/^[a-zA-Z0-9_-]+$/'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_PARAMETER',
                        'message' => 'Invalid extension name format',
                        'details' => $validator->errors()
                    ]
                ], 400);
            }

            // 解析查询参数
            $includePrerelease = $request->boolean('include_prerelease', false);
            $phpVersion = $request->string('php_version', '');
            $limit = $request->integer('limit', 50);
            $offset = $request->integer('offset', 0);

            // 使用缓存，1小时过期
            $cacheKey = "api.pecl.versions.{$extensionName}." . md5(serialize([
                'include_prerelease' => $includePrerelease,
                'php_version' => $phpVersion,
                'limit' => $limit,
                'offset' => $offset
            ]));

            $result = Cache::remember($cacheKey, 3600, function () use ($extensionName, $includePrerelease, $phpVersion, $limit, $offset) {
                return $this->getExtensionVersions($extensionName, $includePrerelease, $phpVersion, $limit, $offset);
            });

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'EXTENSION_NOT_FOUND',
                        'message' => "PECL extension '{$extensionName}' not found"
                    ]
                ], 404);
            }

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
                    'message' => 'Failed to get PECL extension versions',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }

    /**
     * 获取PECL扩展详细信息
     *
     * @param Request $request
     * @param string $extensionName
     * @return JsonResponse
     */
    public function show(Request $request, string $extensionName): JsonResponse
    {
        try {
            // 验证扩展名
            $validator = Validator::make(['extension_name' => $extensionName], [
                'extension_name' => 'required|string|min:1|max:50|regex:/^[a-zA-Z0-9_-]+$/'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_PARAMETER',
                        'message' => 'Invalid extension name format',
                        'details' => $validator->errors()
                    ]
                ], 400);
            }

            // 使用缓存，2小时过期
            $cacheKey = "api.pecl.info.{$extensionName}";

            $result = Cache::remember($cacheKey, 7200, function () use ($extensionName) {
                return $this->getExtensionInfo($extensionName);
            });

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'EXTENSION_NOT_FOUND',
                        'message' => "PECL extension '{$extensionName}' not found"
                    ]
                ], 404);
            }

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
                    'message' => 'Failed to get PECL extension info',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }

    /**
     * 获取扩展版本数据
     *
     * @param string $extensionName
     * @param bool $includePrerelease
     * @param string $phpVersion
     * @param int $limit
     * @param int $offset
     * @return array|null
     */
    protected function getExtensionVersions(string $extensionName, bool $includePrerelease, string $phpVersion, int $limit, int $offset): ?array
    {
        // 检查扩展是否存在
        if (!$this->peclMirrorService->extensionExists($extensionName)) {
            return null;
        }

        $allVersions = $this->peclMirrorService->getExtensionVersions($extensionName, $includePrerelease, $phpVersion);

        $total = count($allVersions);
        $versions = array_slice($allVersions, $offset, $limit);

        return [
            'extension_name' => $extensionName,
            'php_version_filter' => $phpVersion,
            'include_prerelease' => $includePrerelease,
            'total' => $total,
            'count' => count($versions),
            'offset' => $offset,
            'limit' => $limit,
            'versions' => array_map(function ($version) {
                return [
                    'version' => $version['version'],
                    'release_date' => $version['release_date'],
                    'is_prerelease' => $version['is_prerelease'] ?? false,
                    'php_min_version' => $version['php_min_version'] ?? null,
                    'php_max_version' => $version['php_max_version'] ?? null,
                    'download_url' => $version['download_url'],
                    'checksum' => $version['checksum'] ?? null,
                    'size' => $version['size'] ?? null,
                    'changelog' => $version['changelog'] ?? null
                ];
            }, $versions)
        ];
    }

    /**
     * 获取扩展详细信息
     *
     * @param string $extensionName
     * @return array|null
     */
    protected function getExtensionInfo(string $extensionName): ?array
    {
        $info = $this->peclMirrorService->getExtensionInfo($extensionName);

        if (!$info) {
            return null;
        }

        return [
            'name' => $info['name'],
            'summary' => $info['summary'],
            'description' => $info['description'],
            'category' => $info['category'],
            'license' => $info['license'],
            'homepage' => $info['homepage'] ?? null,
            'documentation' => $info['documentation'] ?? null,
            'maintainers' => $info['maintainers'] ?? [],
            'latest_version' => $info['latest_version'],
            'latest_release_date' => $info['latest_release_date'],
            'total_downloads' => $info['total_downloads'] ?? 0,
            'monthly_downloads' => $info['monthly_downloads'] ?? 0,
            'supported_php_versions' => $info['supported_php_versions'] ?? [],
            'dependencies' => $info['dependencies'] ?? [],
            'statistics' => [
                'total_versions' => $info['total_versions'] ?? 0,
                'first_release' => $info['first_release'] ?? null,
                'last_update' => $info['last_update'] ?? null
            ]
        ];
    }
}
