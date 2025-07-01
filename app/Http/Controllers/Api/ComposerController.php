<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ComposerController extends Controller
{
    protected ConfigService $configService;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * 获取所有可用的Composer版本
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function versions(Request $request): JsonResponse
    {
        try {
            // 解析查询参数
            $includePrerelease = $request->boolean('include_prerelease', false);
            $channel = $request->string('channel', 'stable'); // stable, preview, snapshot
            $limit = $request->integer('limit', 50);
            $offset = $request->integer('offset', 0);

            // 验证channel参数
            if (!in_array($channel, ['stable', 'preview', 'snapshot'])) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_PARAMETER',
                        'message' => 'Invalid channel. Must be one of: stable, preview, snapshot'
                    ]
                ], 400);
            }

            // 使用缓存，30分钟过期
            $cacheKey = "api.composer.versions." . md5(serialize([
                'include_prerelease' => $includePrerelease,
                'channel' => $channel,
                'limit' => $limit,
                'offset' => $offset
            ]));

            $result = Cache::remember($cacheKey, 1800, function () use ($includePrerelease, $channel, $limit, $offset) {
                return $this->getComposerVersions($includePrerelease, $channel, $limit, $offset);
            });

            return response()->json([
                'success' => true,
                'data' => $result,
                'timestamp' => now()->toISOString(),
                'cache_ttl' => 1800
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SYSTEM_ERROR',
                    'message' => 'Failed to get Composer versions',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }

    /**
     * 获取最新的Composer版本信息
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function latest(Request $request): JsonResponse
    {
        try {
            $channel = $request->string('channel', 'stable');

            // 验证channel参数
            if (!in_array($channel, ['stable', 'preview', 'snapshot'])) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_PARAMETER',
                        'message' => 'Invalid channel. Must be one of: stable, preview, snapshot'
                    ]
                ], 400);
            }

            // 使用缓存，15分钟过期
            $cacheKey = "api.composer.latest.{$channel}";

            $result = Cache::remember($cacheKey, 900, function () use ($channel) {
                return $this->getLatestComposerVersion($channel);
            });

            return response()->json([
                'success' => true,
                'data' => $result,
                'timestamp' => now()->toISOString(),
                'cache_ttl' => 900
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SYSTEM_ERROR',
                    'message' => 'Failed to get latest Composer version',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }

    /**
     * 获取Composer安装脚本
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function installer(Request $request): JsonResponse
    {
        try {
            $version = $request->string('version', 'latest');
            $format = $request->string('format', 'php'); // php, bash

            if (!in_array($format, ['php', 'bash'])) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_PARAMETER',
                        'message' => 'Invalid format. Must be one of: php, bash'
                    ]
                ], 400);
            }

            $result = [
                'version' => $version,
                'format' => $format,
                'installer_url' => $this->getInstallerUrl($version, $format),
                'checksum_url' => $this->getChecksumUrl($version),
                'instructions' => $this->getInstallInstructions($version, $format)
            ];

            return response()->json([
                'success' => true,
                'data' => $result,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SYSTEM_ERROR',
                    'message' => 'Failed to get Composer installer',
                    'details' => config('app.debug') ? $e->getMessage() : null
                ]
            ], 500);
        }
    }

    /**
     * 获取Composer版本数据
     *
     * @param bool $includePrerelease
     * @param string $channel
     * @param int $limit
     * @param int $offset
     * @return array
     */
    protected function getComposerVersions(bool $includePrerelease, string $channel, int $limit, int $offset): array
    {
        // 模拟数据，实际应该从GitHub API或镜像服务获取
        $allVersions = $this->fetchComposerVersionsFromSource($includePrerelease, $channel);

        $total = count($allVersions);
        $versions = array_slice($allVersions, $offset, $limit);

        return [
            'channel' => $channel,
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
                    'channel' => $version['channel'] ?? 'stable',
                    'download_url' => $version['download_url'],
                    'checksum' => $version['checksum'] ?? null,
                    'size' => $version['size'] ?? null,
                    'php_min_version' => $version['php_min_version'] ?? '7.2.5'
                ];
            }, $versions)
        ];
    }

    /**
     * 获取最新Composer版本
     *
     * @param string $channel
     * @return array
     */
    protected function getLatestComposerVersion(string $channel): array
    {
        $versions = $this->fetchComposerVersionsFromSource(false, $channel);
        $latest = $versions[0] ?? null;

        if (!$latest) {
            throw new \Exception("No Composer version found for channel: {$channel}");
        }

        return [
            'version' => $latest['version'],
            'release_date' => $latest['release_date'],
            'channel' => $channel,
            'download_url' => $latest['download_url'],
            'checksum' => $latest['checksum'] ?? null,
            'size' => $latest['size'] ?? null,
            'php_min_version' => $latest['php_min_version'] ?? '7.2.5',
            'changelog_url' => $latest['changelog_url'] ?? null
        ];
    }

    /**
     * 从源获取Composer版本数据
     *
     * @param bool $includePrerelease
     * @param string $channel
     * @return array
     */
    protected function fetchComposerVersionsFromSource(bool $includePrerelease, string $channel): array
    {
        // 这里应该实现从GitHub API或镜像服务获取真实数据
        // 目前返回模拟数据
        return [
            [
                'version' => '2.7.1',
                'release_date' => '2024-02-09T00:00:00Z',
                'is_prerelease' => false,
                'channel' => 'stable',
                'download_url' => '/composer/2.7.1/composer.phar',
                'checksum' => 'sha256:...',
                'size' => 2097152,
                'php_min_version' => '7.2.5'
            ],
            [
                'version' => '2.7.0',
                'release_date' => '2024-01-15T00:00:00Z',
                'is_prerelease' => false,
                'channel' => 'stable',
                'download_url' => '/composer/2.7.0/composer.phar',
                'checksum' => 'sha256:...',
                'size' => 2097152,
                'php_min_version' => '7.2.5'
            ]
        ];
    }

    /**
     * 获取安装器URL
     *
     * @param string $version
     * @param string $format
     * @return string
     */
    protected function getInstallerUrl(string $version, string $format): string
    {
        $baseUrl = config('app.url');

        if ($format === 'bash') {
            return "{$baseUrl}/composer/installer.sh";
        }

        return "{$baseUrl}/composer/installer.php";
    }

    /**
     * 获取校验和URL
     *
     * @param string $version
     * @return string
     */
    protected function getChecksumUrl(string $version): string
    {
        $baseUrl = config('app.url');
        return "{$baseUrl}/composer/{$version}/composer.phar.sha256";
    }

    /**
     * 获取安装说明
     *
     * @param string $version
     * @param string $format
     * @return array
     */
    protected function getInstallInstructions(string $version, string $format): array
    {
        if ($format === 'bash') {
            return [
                'curl -sS ' . $this->getInstallerUrl($version, $format) . ' | bash',
                'sudo mv composer.phar /usr/local/bin/composer',
                'composer --version'
            ];
        }

        return [
            'php -r "copy(\'' . $this->getInstallerUrl($version, $format) . '\', \'composer-setup.php\');"',
            'php composer-setup.php',
            'php -r "unlink(\'composer-setup.php\');"',
            'sudo mv composer.phar /usr/local/bin/composer',
            'composer --version'
        ];
    }
}
