<?php

namespace Mirror\Service;

/**
 * GitHub版本发现服务
 *
 * 从GitHub API获取仓库的所有可用版本
 */
class GithubVersionDiscovery
{
    private $timeout = 30;

    /**
     * 获取GitHub仓库的所有可用版本
     *
     * @param string $sourceUrl GitHub源地址
     * @return array 版本数组
     */
    public function getRepositoryVersions($sourceUrl)
    {
        try {
            $repoInfo = $this->parseGithubSource($sourceUrl);
            if (!$repoInfo) {
                return [];
            }

            // 获取所有标签（处理分页）
            $allTags = $this->fetchAllTags($repoInfo['owner'], $repoInfo['repo']);

            if (empty($allTags)) {
                return [];
            }

            $versions = [];

            foreach ($allTags as $tag) {
                $tagName = $tag['name'];

                // 清理版本号（移除v前缀等）
                $version = $this->cleanVersionNumber($tagName);

                if ($version && $this->isValidVersion($version)) {
                    $versions[] = $version;
                }
            }

            // 过滤稳定版本
            $stableVersions = $this->filterVersions($versions, true);

            // 排序版本
            usort($stableVersions, 'version_compare');

            return array_unique($stableVersions);

        } catch (\Exception $e) {
            echo "  错误: 获取GitHub仓库版本失败: " . $e->getMessage() . "\n";
            return [];
        }
    }

    /**
     * 获取仓库的最新版本
     *
     * @param string $sourceUrl GitHub源地址
     * @return string|null 最新版本号
     */
    public function getLatestVersion($sourceUrl)
    {
        $versions = $this->getRepositoryVersions($sourceUrl);

        if (empty($versions)) {
            return null;
        }

        return end($versions);
    }

    /**
     * 获取仓库的版本范围（最小和最大版本）
     *
     * @param string $sourceUrl GitHub源地址
     * @return array|null [最小版本, 最大版本]
     */
    public function getVersionRange($sourceUrl)
    {
        $versions = $this->getRepositoryVersions($sourceUrl);

        if (empty($versions)) {
            return null;
        }

        return [reset($versions), end($versions)];
    }

    /**
     * 获取仓库的发布信息
     *
     * @param string $sourceUrl GitHub源地址
     * @return array 发布信息数组
     */
    public function getRepositoryReleases($sourceUrl)
    {
        try {
            $repoInfo = $this->parseGithubSource($sourceUrl);
            if (!$repoInfo) {
                return [];
            }

            $apiUrl = "https://api.github.com/repos/{$repoInfo['owner']}/{$repoInfo['repo']}/releases";
            $data = $this->fetchApiData($apiUrl);

            if (!$data) {
                return [];
            }

            $releases = [];

            foreach ($data as $release) {
                $version = $this->cleanVersionNumber($release['tag_name']);

                if ($version && $this->isValidVersion($version)) {
                    $releases[] = [
                        'version' => $version,
                        'tag_name' => $release['tag_name'],
                        'name' => $release['name'],
                        'published_at' => $release['published_at'],
                        'prerelease' => $release['prerelease'],
                        'draft' => $release['draft'],
                    ];
                }
            }

            // 按版本排序
            usort($releases, function($a, $b) {
                return version_compare($a['version'], $b['version']);
            });

            return $releases;

        } catch (\Exception $e) {
            echo "  错误: 获取GitHub仓库发布信息失败: " . $e->getMessage() . "\n";
            return [];
        }
    }

    /**
     * 获取稳定版本（排除预发布版本）
     *
     * @param string $sourceUrl GitHub源地址
     * @return array 稳定版本数组
     */
    public function getStableVersions($sourceUrl)
    {
        $releases = $this->getRepositoryReleases($sourceUrl);
        $stableVersions = [];

        foreach ($releases as $release) {
            if (!$release['prerelease'] && !$release['draft']) {
                $stableVersions[] = $release['version'];
            }
        }

        return $stableVersions;
    }

    /**
     * 解析GitHub源地址，提取owner和repo信息
     *
     * @param string $sourceUrl GitHub源地址
     * @return array|null 包含owner和repo的数组，失败返回null
     */
    private function parseGithubSource($sourceUrl)
    {
        // 匹配GitHub源地址格式：https://github.com/{owner}/{repo}/archive/refs/tags
        if (preg_match('#^https://github\.com/([^/]+)/([^/]+)/archive/refs/tags$#', $sourceUrl, $matches)) {
            return [
                'owner' => $matches[1],
                'repo' => $matches[2]
            ];
        }

        return null;
    }

    /**
     * 清理版本号
     *
     * @param string $tagName 标签名
     * @return string|null 清理后的版本号
     */
    private function cleanVersionNumber($tagName)
    {
        // 移除常见的前缀
        $version = preg_replace('/^[vV]/', '', $tagName);

        // 移除其他常见前缀
        $version = preg_replace('/^(release[-_]?|rel[-_]?)/i', '', $version);

        return $version;
    }

    /**
     * 验证版本号格式
     *
     * @param string $version 版本号
     * @return bool 是否为有效版本号
     */
    private function isValidVersion($version)
    {
        // 基本的版本号格式验证
        return preg_match('/^\d+(\.\d+)*([.-]?(alpha|beta|rc|dev)\d*)?$/i', $version);
    }

    /**
     * 获取仓库的所有标签（处理分页）
     *
     * @param string $owner 仓库所有者
     * @param string $repo 仓库名
     * @return array 所有标签数组
     */
    private function fetchAllTags($owner, $repo)
    {
        $allTags = [];
        $page = 1;
        $perPage = 100; // GitHub API 最大每页100个

        do {
            $url = "https://api.github.com/repos/{$owner}/{$repo}/tags?page={$page}&per_page={$perPage}";
            $tags = $this->fetchApiData($url);

            if (!$tags || !is_array($tags)) {
                break;
            }

            $allTags = array_merge($allTags, $tags);
            $page++;

            // 如果返回的标签数量少于每页数量，说明已经是最后一页
        } while (count($tags) === $perPage);

        return $allTags;
    }

    /**
     * 从GitHub API获取数据
     *
     * @param string $url API URL
     * @return array|null API数据
     */
    private function fetchApiData($url)
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'user_agent' => 'PVM-Mirror/1.0',
                'header' => [
                    'Accept: application/vnd.github.v3+json'
                ]
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return null;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    /**
     * 过滤版本（排除预发布版本）
     *
     * @param array $versions 版本数组
     * @param bool $stableOnly 是否只返回稳定版本
     * @return array 过滤后的版本数组
     */
    public function filterVersions($versions, $stableOnly = true)
    {
        if (!$stableOnly) {
            return $versions;
        }

        $filtered = [];
        foreach ($versions as $version) {
            // 排除包含alpha、beta、rc、dev等的版本
            if (!preg_match('/(alpha|beta|rc|dev)/i', $version)) {
                $filtered[] = $version;
            }
        }

        return $filtered;
    }

    /**
     * 设置API超时时间
     *
     * @param int $timeout 超时时间（秒）
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }
}
