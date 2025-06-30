<?php

namespace Mirror\Mirror;

/**
 * 镜像状态管理类
 */
class MirrorStatus
{
    /**
     * 格式化文件大小
     *
     * @param int $size 文件大小（字节）
     * @return string 格式化后的大小
     */
    public function formatSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
    /**
     * 获取镜像状态
     *
     * @return array
     */
    public function getStatus()
    {
        // 获取数据目录
        $configManager = new \Mirror\Config\ConfigManager();
        $dataDir = $configManager->getDataDir();

        $phpDir = $dataDir . '/php';
        $peclDir = $dataDir . '/pecl';
        $extensionsDir = $dataDir . '/extensions';
        $composerDir = $dataDir . '/composer';

        $phpFiles = is_dir($phpDir) ? glob($phpDir . '/*.tar.gz') : [];
        $peclFiles = is_dir($peclDir) ? glob($peclDir . '/*.tgz') : [];

        $extensionFiles = [];
        $extensionDirs = [];
        if (is_dir($extensionsDir)) {
            $extensionDirs = glob($extensionsDir . '/*', GLOB_ONLYDIR);
            foreach ($extensionDirs as $dir) {
                $files = glob($dir . '/*.tar.gz');
                $extensionFiles = array_merge($extensionFiles, $files);
            }
        }

        $composerFiles = is_dir($composerDir) ? glob($composerDir . '/*.phar') : [];

        $allFiles = array_merge($phpFiles, $peclFiles, $extensionFiles, $composerFiles);
        $totalSize = 0;
        $lastUpdate = 0;

        foreach ($allFiles as $file) {
            $totalSize += filesize($file);
            $mtime = filemtime($file);
            if ($mtime > $lastUpdate) {
                $lastUpdate = $mtime;
            }
        }

        return [
            'php_files' => count($phpFiles),
            'pecl_files' => count($peclFiles),
            'extension_dirs' => $extensionDirs,
            'extension_files' => count($extensionFiles),
            'composer_files' => count($composerFiles),
            'total_files' => count($allFiles),
            'total_size' => $totalSize,
            'last_update' => $lastUpdate,
        ];
    }

    /**
     * 获取PHP源码包列表
     *
     * @return array
     */
    public function getPhpList()
    {
        // 获取数据目录
        $configManager = new \Mirror\Config\ConfigManager();
        $dataDir = $configManager->getDataDir();

        $phpDir = $dataDir . '/php';
        $files = is_dir($phpDir) ? glob($phpDir . '/*.tar.gz') : [];

        $result = [];
        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/php-([0-9.]+)\.tar\.gz/', $filename, $matches)) {
                $version = $matches[1];
                $majorVersion = explode('.', $version)[0] . '.' . explode('.', $version)[1];

                if (!isset($result[$majorVersion])) {
                    $result[$majorVersion] = [];
                }

                $result[$majorVersion][] = [
                    'version' => $version,
                    'filename' => $filename,
                    'size' => filesize($file),
                    'url' => '/php/' . $filename,
                ];
            }
        }

        return $result;
    }

    /**
     * 获取PECL扩展包列表
     *
     * @return array
     */
    public function getPeclList()
    {
        // 获取数据目录
        $configManager = new \Mirror\Config\ConfigManager();
        $dataDir = $configManager->getDataDir();

        $peclDir = $dataDir . '/pecl';
        $files = is_dir($peclDir) ? glob($peclDir . '/*.tgz') : [];

        $result = [];
        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/([a-zA-Z0-9_]+)-([0-9.]+)\.tgz/', $filename, $matches)) {
                $extension = $matches[1];
                $version = $matches[2];

                if (!isset($result[$extension])) {
                    $result[$extension] = [];
                }

                $result[$extension][] = [
                    'version' => $version,
                    'filename' => $filename,
                    'size' => filesize($file),
                    'url' => '/pecl/' . $filename,
                ];
            }
        }

        return $result;
    }

    /**
     * 获取特定扩展源码列表
     *
     * @return array
     */
    public function getExtensionsList()
    {
        // 获取数据目录
        $configManager = new \Mirror\Config\ConfigManager();
        $dataDir = $configManager->getDataDir();

        $extensionsDir = $dataDir . '/extensions';
        $result = [];

        if (is_dir($extensionsDir)) {
            $extensionDirs = glob($extensionsDir . '/*', GLOB_ONLYDIR);

            foreach ($extensionDirs as $dir) {
                $extension = basename($dir);
                $files = glob($dir . '/*.tar.gz');

                $result[$extension] = [];

                foreach ($files as $file) {
                    $filename = basename($file);

                    $result[$extension][] = [
                        'filename' => $filename,
                        'size' => filesize($file),
                        'url' => '/extensions/' . $extension . '/' . $filename,
                    ];
                }
            }
        }

        return $result;
    }

    /**
     * 获取Composer包列表
     *
     * @return array
     */
    public function getComposerList()
    {
        // 获取数据目录
        $configManager = new \Mirror\Config\ConfigManager();
        $dataDir = $configManager->getDataDir();

        $composerDir = $dataDir . '/composer';
        $files = is_dir($composerDir) ? glob($composerDir . '/*.phar') : [];

        $result = [];
        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/composer-([0-9.]+)\.phar/', $filename, $matches)) {
                $version = $matches[1];

                $result[] = [
                    'version' => $version,
                    'filename' => $filename,
                    'size' => filesize($file),
                    'url' => '/composer/' . $filename,
                ];
            }
        }

        return $result;
    }

    /**
     * 获取基本状态信息（用于ping请求，避免耗时操作）
     *
     * @return array
     */
    public function getBasicStatus()
    {
        // 获取数据目录
        $configManager = new \Mirror\Config\ConfigManager();
        $dataDir = $configManager->getDataDir();

        // 快速统计文件数量，不计算大小和详细信息
        $phpDir = $dataDir . '/php';
        $peclDir = $dataDir . '/pecl';

        $phpCount = 0;
        $peclCount = 0;

        // 快速统计PHP文件数量
        if (is_dir($phpDir)) {
            $phpFiles = glob($phpDir . '/*.tar.gz');
            $phpCount = $phpFiles ? count($phpFiles) : 0;
        }

        // 快速统计PECL文件数量
        if (is_dir($peclDir)) {
            $peclFiles = glob($peclDir . '/*.tgz');
            $peclCount = $peclFiles ? count($peclFiles) : 0;
        }

        return [
            'php_count' => $phpCount,
            'pecl_count' => $peclCount,
            'status' => 'online',
            'timestamp' => time(),
        ];
    }
}
