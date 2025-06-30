<?php

namespace Mirror\Service;

/**
 * PECL版本发现服务
 * 
 * 从PECL官方API获取扩展的所有可用版本
 */
class PeclVersionDiscovery
{
    private $baseApiUrl = 'https://pecl.php.net/rest/r';
    private $timeout = 30;

    /**
     * 获取指定扩展的所有可用版本
     *
     * @param string $extensionName 扩展名
     * @return array 版本数组
     */
    public function getExtensionVersions($extensionName)
    {
        try {
            $apiUrl = $this->baseApiUrl . '/' . $extensionName . '/allreleases.xml';
            $data = $this->fetchApiData($apiUrl);
            
            if (!$data) {
                return [];
            }

            $versions = [];
            
            // 解析XML数据
            $xml = simplexml_load_string($data);
            if ($xml === false) {
                return [];
            }

            // 遍历所有release
            foreach ($xml->r as $release) {
                $version = (string)$release->v;
                $state = (string)$release->s;
                
                // 只获取稳定版本
                if ($state === 'stable') {
                    $versions[] = $version;
                }
            }

            // 排序版本
            usort($versions, 'version_compare');
            
            return array_unique($versions);
            
        } catch (Exception $e) {
            echo "  错误: 获取PECL扩展 $extensionName 版本失败: " . $e->getMessage() . "\n";
            return [];
        }
    }

    /**
     * 获取扩展的最新版本
     *
     * @param string $extensionName 扩展名
     * @return string|null 最新版本号
     */
    public function getLatestVersion($extensionName)
    {
        $versions = $this->getExtensionVersions($extensionName);
        
        if (empty($versions)) {
            return null;
        }

        // 返回最新版本
        return end($versions);
    }

    /**
     * 获取扩展的版本范围（最小和最大版本）
     *
     * @param string $extensionName 扩展名
     * @return array|null [最小版本, 最大版本]
     */
    public function getVersionRange($extensionName)
    {
        $versions = $this->getExtensionVersions($extensionName);
        
        if (empty($versions)) {
            return null;
        }

        return [reset($versions), end($versions)];
    }

    /**
     * 获取多个扩展的版本信息
     *
     * @param array $extensionNames 扩展名数组
     * @return array 扩展版本信息数组
     */
    public function getMultipleExtensionVersions($extensionNames)
    {
        $result = [];
        
        foreach ($extensionNames as $extensionName) {
            echo "  获取扩展 $extensionName 的版本信息...\n";
            $versions = $this->getExtensionVersions($extensionName);
            
            if (!empty($versions)) {
                $result[$extensionName] = $versions;
                echo "    发现 " . count($versions) . " 个版本\n";
            } else {
                echo "    警告: 无法获取版本信息\n";
            }
        }
        
        return $result;
    }

    /**
     * 检查扩展是否存在
     *
     * @param string $extensionName 扩展名
     * @return bool 是否存在
     */
    public function extensionExists($extensionName)
    {
        try {
            $apiUrl = $this->baseApiUrl . '/' . $extensionName . '/info.xml';
            $data = $this->fetchApiData($apiUrl);
            
            return $data !== null;
            
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 获取扩展的基本信息
     *
     * @param string $extensionName 扩展名
     * @return array|null 扩展信息
     */
    public function getExtensionInfo($extensionName)
    {
        try {
            $apiUrl = $this->baseApiUrl . '/' . $extensionName . '/info.xml';
            $data = $this->fetchApiData($apiUrl);
            
            if (!$data) {
                return null;
            }

            $xml = simplexml_load_string($data);
            if ($xml === false) {
                return null;
            }

            return [
                'name' => (string)$xml->n,
                'summary' => (string)$xml->s,
                'description' => (string)$xml->d,
                'license' => (string)$xml->l,
            ];
            
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * 从PECL API获取数据
     *
     * @param string $url API URL
     * @return string|null API响应数据
     */
    private function fetchApiData($url)
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => $this->timeout,
                'user_agent' => 'PVM-Mirror/1.0',
            ]
        ]);

        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }

        return $response;
    }

    /**
     * 过滤版本（根据状态）
     *
     * @param string $extensionName 扩展名
     * @param array $states 允许的状态数组，如 ['stable', 'beta']
     * @return array 过滤后的版本数组
     */
    public function getVersionsByState($extensionName, $states = ['stable'])
    {
        try {
            $apiUrl = $this->baseApiUrl . '/' . $extensionName . '/allreleases.xml';
            $data = $this->fetchApiData($apiUrl);
            
            if (!$data) {
                return [];
            }

            $versions = [];
            
            $xml = simplexml_load_string($data);
            if ($xml === false) {
                return [];
            }

            foreach ($xml->r as $release) {
                $version = (string)$release->v;
                $state = (string)$release->s;
                
                if (in_array($state, $states)) {
                    $versions[] = $version;
                }
            }

            usort($versions, 'version_compare');
            
            return array_unique($versions);
            
        } catch (Exception $e) {
            return [];
        }
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
