<?php

namespace Mirror\Service;

/**
 * PHP版本发现服务
 *
 * 从PHP官方API获取所有可用版本
 */
class PhpVersionDiscovery
{
    private $apiUrl = 'https://www.php.net/releases/index.php?json=1';
    private $timeout = 30;

    /**
     * 获取所有可用的PHP版本
     *
     * @return array 版本数组
     */
    public function getAvailableVersions()
    {
        try {
            // 从 GitHub 获取完整的版本列表
            $githubVersions = $this->getVersionsFromGithub();

            if (!empty($githubVersions)) {
                echo "  从 GitHub 获取到 " . count($githubVersions) . " 个版本\n";
                return $githubVersions;
            }

            // 如果 GitHub 失败，回退到官方 API
            echo "  GitHub 获取失败，尝试官方 API...\n";
            $apiVersions = $this->getVersionsFromOfficialApi();

            if (!empty($apiVersions)) {
                echo "  从官方 API 获取到 " . count($apiVersions) . " 个版本\n";
                return $apiVersions;
            }

            echo "  所有版本发现方式都失败\n";
            return [];

        } catch (\Exception $e) {
            echo "  错误: 获取PHP版本失败: " . $e->getMessage() . "\n";
            return [];
        }
    }

    /**
     * 从 GitHub 获取完整的 PHP 版本列表
     *
     * @return array 版本数组
     */
    private function getVersionsFromGithub()
    {
        try {
            $githubApiUrl = "https://api.github.com/repos/php/php-src/tags?per_page=100";
            $allTags = $this->fetchAllGithubTags($githubApiUrl);

            if (empty($allTags)) {
                return [];
            }

            $versions = [];
            foreach ($allTags as $tag) {
                $tagName = $tag['name'];

                // 匹配 PHP 版本格式：php-x.y.z
                if (preg_match('/^php-(\d+\.\d+\.\d+)$/', $tagName, $matches)) {
                    $version = $matches[1];

                    // 过滤掉太老的版本（5.4 以下）
                    if (version_compare($version, '5.4.0', '>=')) {
                        $versions[] = $version;
                    }
                }
            }

            // 排序版本
            usort($versions, 'version_compare');

            return array_unique($versions);

        } catch (\Exception $e) {
            echo "  GitHub API 错误: " . $e->getMessage() . "\n";
            return [];
        }
    }

    /**
     * 从官方 API 获取版本信息（作为备用）
     *
     * @return array 版本数组
     */
    private function getVersionsFromOfficialApi()
    {
        try {
            $data = $this->fetchApiData();
            if (!$data) {
                return [];
            }

            $versions = [];

            // 解析官方 API 数据
            foreach ($data as $majorVersion => $versionInfo) {
                if (isset($versionInfo['version'])) {
                    $version = $versionInfo['version'];

                    // 检查是否为博物馆版本（已废弃）
                    $isMuseum = isset($versionInfo['museum']) && $versionInfo['museum'];

                    // 只添加非博物馆版本
                    if (!$isMuseum) {
                        $versions[] = $version;
                    }
                }
            }

            // 排序版本
            usort($versions, 'version_compare');

            return array_unique($versions);

        } catch (\Exception $e) {
            echo "  官方 API 错误: " . $e->getMessage() . "\n";
            return [];
        }
    }

    /**
     * 获取指定主版本的所有子版本
     *
     * @param string $majorVersion 主版本号，如 '8.3'
     * @return array 子版本数组
     */
    public function getMajorVersionReleases($majorVersion)
    {
        try {
            // 尝试从GitHub API获取更详细的版本信息
            $githubApiUrl = "https://api.github.com/repos/php/php-src/tags";
            $data = $this->fetchGithubData($githubApiUrl);

            if (!$data) {
                return [];
            }

            $versions = [];
            foreach ($data as $tag) {
                $tagName = $tag['name'];
                // 匹配版本格式，如 php-8.3.1
                if (preg_match('/^php-(' . preg_quote($majorVersion, '/') . '\.\d+)$/', $tagName, $matches)) {
                    $versions[] = $matches[1];
                }
            }

            // 排序版本
            usort($versions, 'version_compare');

            return array_unique($versions);

        } catch (\Exception $e) {
            echo "  错误: 获取PHP主版本 $majorVersion 的子版本失败: " . $e->getMessage() . "\n";
            return [];
        }
    }

    /**
     * 获取所有主版本及其最新版本
     *
     * @return array 主版本和最新版本的映射
     */
    public function getMajorVersionsWithLatest()
    {
        $allVersions = $this->getAvailableVersions();
        $majorVersions = [];

        foreach ($allVersions as $version) {
            if (preg_match('/^(\d+\.\d+)\./', $version, $matches)) {
                $majorVersion = $matches[1];

                if (!isset($majorVersions[$majorVersion]) ||
                    version_compare($version, $majorVersions[$majorVersion], '>')) {
                    $majorVersions[$majorVersion] = $version;
                }
            }
        }

        return $majorVersions;
    }

    /**
     * 从PHP官方API获取数据
     *
     * @return array|null API数据
     */
    private function fetchApiData()
    {
        // 优先使用 curl
        if (function_exists('curl_init')) {
            return $this->fetchApiDataWithCurl();
        }

        // 回退到 file_get_contents
        return $this->fetchApiDataWithFileGetContents();
    }

    /**
     * 使用 curl 获取 API 数据
     *
     * @return array|null API数据
     */
    private function fetchApiDataWithCurl()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PVM-Mirror/1.0');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($error)) {
            echo "  curl 错误: $error\n";
            return null;
        }

        if ($httpCode !== 200) {
            echo "  HTTP 错误: $httpCode\n";
            return null;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "  JSON 解析错误: " . json_last_error_msg() . "\n";
            return null;
        }

        return $data;
    }

    /**
     * 使用 file_get_contents 获取 API 数据
     *
     * @return array|null API数据
     */
    private function fetchApiDataWithFileGetContents()
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'user_agent' => 'PVM-Mirror/1.0',
            ]
        ]);

        $response = @file_get_contents($this->apiUrl, false, $context);

        if ($response === false) {
            echo "  file_get_contents 失败\n";
            return null;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "  JSON 解析错误: " . json_last_error_msg() . "\n";
            return null;
        }

        return $data;
    }

    /**
     * 获取所有 GitHub 标签（处理分页）
     *
     * @param string $baseUrl GitHub API URL
     * @return array 所有标签数组
     */
    private function fetchAllGithubTags($baseUrl)
    {
        $allTags = [];
        $page = 1;
        $perPage = 100; // GitHub API 最大每页100个

        do {
            $url = $baseUrl . "&page={$page}";
            $tags = $this->fetchGithubData($url);

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
     * @param string $url GitHub API URL
     * @return array|null API数据
     */
    private function fetchGithubData($url)
    {
        // 优先使用 curl
        if (function_exists('curl_init')) {
            return $this->fetchGithubDataWithCurl($url);
        }

        // 回退到 file_get_contents
        return $this->fetchGithubDataWithFileGetContents($url);
    }

    /**
     * 使用 curl 获取 GitHub API 数据
     *
     * @param string $url GitHub API URL
     * @return array|null API数据
     */
    private function fetchGithubDataWithCurl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PVM-Mirror/1.0');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/vnd.github.v3+json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($error)) {
            return null;
        }

        if ($httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    /**
     * 使用 file_get_contents 获取 GitHub API 数据
     *
     * @param string $url GitHub API URL
     * @return array|null API数据
     */
    private function fetchGithubDataWithFileGetContents($url)
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
     * 过滤版本（排除alpha、beta、RC等）
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
            // 排除包含alpha、beta、RC等的版本
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
