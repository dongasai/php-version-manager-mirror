<?php

namespace Mirror\Command;

use Mirror\Log\LogManager;

/**
 * 日志命令类
 */
class LogCommand extends AbstractCommand
{
    /**
     * 日志管理器
     *
     * @var LogManager
     */
    private $logManager;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct('log', '管理日志');
        $this->logManager = new LogManager();
    }

    /**
     * 执行命令
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    public function execute(array $args = [])
    {
        // 如果没有参数，显示帮助信息
        if (empty($args)) {
            return $this->showHelp();
        }

        // 获取操作
        $action = $args[0];

        // 执行操作
        switch ($action) {
            case 'show':
            case 'view':
                return $this->handleShowCommand($args);

            case 'list':
                return $this->showPvmLogList();

            case 'path':
                return $this->showPvmLogPath();

            case 'clear':
                return $this->handleClearCommand($args);

            case 'tail':
                return $this->handleTailCommand($args);

            case 'types':
                return $this->showLogTypes();

            // 传统日志类型操作（向后兼容）
            case 'legacy-show':
                if (count($args) < 2) {
                    echo "错误: 缺少日志类型\n";
                    return $this->showHelp();
                }
                $type = $args[1];
                $lines = isset($args[2]) ? (int)$args[2] : 10;
                return $this->showLog($type, $lines);

            case 'legacy-clear':
                if (count($args) < 2) {
                    echo "错误: 缺少日志类型\n";
                    return $this->showHelp();
                }
                $type = $args[1];
                return $this->clearLog($type);

            case 'legacy-path':
                if (count($args) < 2) {
                    echo "错误: 缺少日志类型\n";
                    return $this->showHelp();
                }
                $type = $args[1];
                return $this->showLogPath($type);

            case 'help':
                return $this->showHelp();

            default:
                echo "未知操作: $action\n";
                return $this->showHelp();
        }
    }

    /**
     * 显示日志内容
     *
     * @param string $type 日志类型
     * @param int $lines 行数
     * @return int 退出代码
     */
    private function showLog($type, $lines = 10)
    {
        // 验证日志类型
        if (!$this->isValidLogType($type)) {
            echo "错误: 无效的日志类型: $type\n";
            echo "有效的日志类型: system, access, error, sync, download\n";
            return 1;
        }

        // 获取日志内容
        $logs = $this->logManager->getLogContent($type, $lines);

        if (empty($logs)) {
            echo "日志为空\n";
            return 0;
        }

        echo "最近 " . count($logs) . " 条 $type 日志:\n";
        foreach ($logs as $log) {
            echo $log;
        }

        return 0;
    }

    /**
     * 清空日志
     *
     * @param string $type 日志类型
     * @return int 退出代码
     */
    private function clearLog($type)
    {
        // 验证日志类型
        if (!$this->isValidLogType($type)) {
            echo "错误: 无效的日志类型: $type\n";
            echo "有效的日志类型: system, access, error, sync, download\n";
            return 1;
        }

        // 清空日志
        if ($this->logManager->clearLog($type)) {
            echo "$type 日志已清空\n";
            return 0;
        } else {
            echo "清空 $type 日志失败\n";
            return 1;
        }
    }

    /**
     * 显示日志文件路径
     *
     * @param string $type 日志类型
     * @return int 退出代码
     */
    private function showLogPath($type)
    {
        // 验证日志类型
        if (!$this->isValidLogType($type)) {
            echo "错误: 无效的日志类型: $type\n";
            echo "有效的日志类型: system, access, error, sync, download\n";
            return 1;
        }

        // 获取日志文件路径
        $path = $this->logManager->getLogFile($type);
        echo "$type 日志文件路径: $path\n";

        return 0;
    }

    /**
     * 显示日志类型
     *
     * @return int 退出代码
     */
    private function showLogTypes()
    {
        echo "可用的日志类型:\n";
        echo "  system    - 系统日志\n";
        echo "  access    - 访问日志\n";
        echo "  error     - 错误日志\n";
        echo "  sync      - 同步日志\n";
        echo "  download  - 下载日志\n";

        return 0;
    }

    /**
     * 验证日志类型
     *
     * @param string $type 日志类型
     * @return bool 是否有效
     */
    private function isValidLogType($type)
    {
        $validTypes = ['system', 'access', 'error', 'sync', 'download'];
        return in_array($type, $validTypes);
    }

    // ========== PVM风格的日志方法 ==========

    /**
     * 处理show命令
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    private function handleShowCommand($args)
    {
        // 解析参数
        $lines = 50; // 默认行数
        $logFile = null;

        // 检查是否指定了特定的日志文件
        if (isset($args[1]) && strpos($args[1], '--') !== 0) {
            $logFile = $args[1];
        }

        // 解析选项
        foreach ($args as $arg) {
            if (strpos($arg, '--lines=') === 0) {
                $lines = (int)substr($arg, 8);
            } elseif (strpos($arg, '-n') === 0) {
                $lines = (int)substr($arg, 2);
            }
        }

        return $this->showPvmLog($logFile, $lines);
    }

    /**
     * 处理clear命令
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    private function handleClearCommand($args)
    {
        // 解析参数
        $days = 30; // 默认保留天数
        $force = false;

        // 解析选项
        foreach ($args as $arg) {
            if (strpos($arg, '--days=') === 0) {
                $days = (int)substr($arg, 7);
            } elseif ($arg === '--force' || $arg === '-f') {
                $force = true;
            }
        }

        return $this->clearPvmLogs($days, $force);
    }

    /**
     * 处理tail命令
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    private function handleTailCommand($args)
    {
        // 解析参数
        $lines = 10; // 默认行数

        // 解析选项
        foreach ($args as $arg) {
            if (strpos($arg, '--lines=') === 0) {
                $lines = (int)substr($arg, 8);
            }
        }

        return $this->tailPvmLog($lines);
    }

    /**
     * 显示PVM风格的日志内容
     *
     * @param string|null $logFile 指定的日志文件
     * @param int $lines 显示行数
     * @return int 退出代码
     */
    private function showPvmLog($logFile = null, $lines = 50)
    {
        if ($logFile) {
            // 显示指定的日志文件
            $logPath = $this->getPvmLogRootDir() . '/' . $logFile;
            if (!file_exists($logPath)) {
                echo "错误: 日志文件不存在: $logFile\n";
                return 1;
            }
        } else {
            // 显示当前日志文件
            $logPath = LogManager::getCurrentPvmLogFile();
            if (!$logPath || !file_exists($logPath)) {
                echo "当前没有活动的日志文件\n";
                return 0;
            }
        }

        // 读取日志文件的最后几行
        $logs = $this->readLastLines($logPath, $lines);

        if (empty($logs)) {
            echo "日志为空\n";
            return 0;
        }

        echo "显示日志文件: " . basename($logPath) . "\n";
        echo "最后 " . count($logs) . " 行:\n\n";
        foreach ($logs as $log) {
            echo $log;
        }

        return 0;
    }

    /**
     * 显示PVM日志文件列表
     *
     * @return int 退出代码
     */
    private function showPvmLogList()
    {
        $logDir = $this->getPvmLogRootDir();

        if (!is_dir($logDir)) {
            echo "日志目录不存在: $logDir\n";
            return 1;
        }

        echo "PVM日志文件列表:\n";
        echo "日志目录: $logDir\n\n";

        $this->listLogFiles($logDir, $logDir);

        return 0;
    }

    /**
     * 显示PVM日志路径信息
     *
     * @return int 退出代码
     */
    private function showPvmLogPath()
    {
        $logDir = $this->getPvmLogRootDir();
        $currentLogFile = LogManager::getCurrentPvmLogFile();

        echo "PVM日志路径信息:\n";
        echo "日志根目录: $logDir\n";

        if ($currentLogFile) {
            echo "当前日志文件: $currentLogFile\n";
        } else {
            echo "当前日志文件: 无\n";
        }

        return 0;
    }

    /**
     * 清理过期的PVM日志
     *
     * @param int $days 保留天数
     * @param bool $force 是否强制执行
     * @return int 退出代码
     */
    private function clearPvmLogs($days, $force)
    {
        $logDir = $this->getPvmLogRootDir();

        if (!is_dir($logDir)) {
            echo "日志目录不存在: $logDir\n";
            return 1;
        }

        if (!$force) {
            echo "将清理 $days 天前的日志文件，是否继续？ (y/N): ";
            $input = trim(fgets(STDIN));
            if (strtolower($input) !== 'y') {
                echo "操作已取消\n";
                return 0;
            }
        }

        $cutoffTime = time() - ($days * 24 * 60 * 60);
        $deletedCount = 0;

        $this->clearOldLogFiles($logDir, $cutoffTime, $deletedCount);

        echo "已清理 $deletedCount 个过期日志文件\n";
        return 0;
    }

    /**
     * 实时查看PVM日志
     *
     * @param int $lines 初始显示行数
     * @return int 退出代码
     */
    private function tailPvmLog($lines)
    {
        $logFile = LogManager::getCurrentPvmLogFile();

        if (!$logFile || !file_exists($logFile)) {
            echo "当前没有活动的日志文件\n";
            return 1;
        }

        echo "实时监控日志文件: " . basename($logFile) . "\n";
        echo "按 Ctrl+C 退出\n\n";

        // 显示最后几行
        $logs = $this->readLastLines($logFile, $lines);
        foreach ($logs as $log) {
            echo $log;
        }

        // 实时监控（简单实现）
        $lastSize = filesize($logFile);
        while (true) {
            clearstatcache();
            $currentSize = filesize($logFile);

            if ($currentSize > $lastSize) {
                $handle = fopen($logFile, 'r');
                fseek($handle, $lastSize);
                while (($line = fgets($handle)) !== false) {
                    echo $line;
                }
                fclose($handle);
                $lastSize = $currentSize;
            }

            sleep(1);
        }

        return 0;
    }

    /**
     * 显示帮助信息
     *
     * @return int 退出代码
     */
    private function showHelp()
    {
        echo "日志管理 (PVM风格)\n";
        echo "用法: pvm-mirror log <操作> [选项]\n\n";
        echo "PVM风格操作:\n";
        echo "  show, view          显示日志内容\n";
        echo "  list               列出所有日志文件\n";
        echo "  path               显示日志文件路径\n";
        echo "  clear              清理过期日志文件\n";
        echo "  tail               实时查看日志\n\n";
        echo "选项:\n";
        echo "  --lines=<数量>      显示的行数，默认50行\n";
        echo "  -n<数量>           显示的行数（简写形式）\n";
        echo "  --days=<天数>       清理时保留的天数，默认30天\n";
        echo "  --force, -f        强制执行，不询问确认\n\n";
        echo "传统日志操作（向后兼容）:\n";
        echo "  legacy-show <类型> [行数]  显示指定类型的日志\n";
        echo "  legacy-clear <类型>        清空指定类型的日志\n";
        echo "  legacy-path <类型>         显示指定类型的日志文件路径\n";
        echo "  types                      显示可用的日志类型\n\n";
        echo "示例:\n";
        echo "  pvm-mirror log show\n";
        echo "  pvm-mirror log show --lines=100\n";
        echo "  pvm-mirror log show 2025/06/01/10-30-45.log\n";
        echo "  pvm-mirror log list\n";
        echo "  pvm-mirror log clear --days=7\n";
        echo "  pvm-mirror log tail --lines=20\n";

        return 0;
    }

    // ========== 辅助方法 ==========

    /**
     * 获取PVM日志根目录
     *
     * @return string
     */
    private function getPvmLogRootDir()
    {
        // 检测是否在开发模式（项目目录中运行）
        $projectRoot = dirname(dirname(dirname(__DIR__)));
        $isDevMode = $this->isDevMode($projectRoot);

        // 开发模式：优先使用项目的 logs 目录
        if ($isDevMode) {
            return $projectRoot . '/logs';
        }

        // 生产模式：使用 PVM 目录下的 log 文件夹
        $homeDir = getenv('HOME');
        $pvmLogDir = $homeDir . '/.pvm/log';

        // 如果 PVM 目录存在，使用它
        if (is_dir($homeDir . '/.pvm')) {
            return $pvmLogDir;
        }

        // 最后备选：使用项目根目录下的 log 文件夹（向后兼容）
        return $projectRoot . '/log';
    }

    /**
     * 检测是否在开发模式
     *
     * @param string $projectRoot 项目根目录
     * @return bool
     */
    private function isDevMode($projectRoot)
    {
        // 检查当前工作目录是否在项目目录内
        $currentDir = getcwd();
        $isInProjectDir = strpos($currentDir, $projectRoot) === 0;

        // 检查是否有项目文件
        $hasProjectFiles = file_exists($projectRoot . '/composer.json') &&
                          file_exists($projectRoot . '/bin/pvm-mirror') &&
                          is_dir($projectRoot . '/srcMirror');

        // 检查是否有开发环境标识
        $hasDevIndicators = is_dir($projectRoot . '/docker') ||
                           is_dir($projectRoot . '/tests') ||
                           file_exists($projectRoot . '/docker-compose.yml');

        return $isInProjectDir && $hasProjectFiles && $hasDevIndicators;
    }

    /**
     * 读取文件的最后几行
     *
     * @param string $file 文件路径
     * @param int $lines 行数
     * @return array 行内容
     */
    private function readLastLines($file, $lines)
    {
        if (!file_exists($file)) {
            return [];
        }

        $logs = [];
        $fileObj = new \SplFileObject($file, 'r');
        $fileObj->seek(PHP_INT_MAX); // 移动到文件末尾
        $totalLines = $fileObj->key(); // 获取总行数

        // 计算起始行
        $startLine = max(0, $totalLines - $lines);

        // 读取指定行数的日志
        $fileObj->seek($startLine);
        while (!$fileObj->eof()) {
            $line = $fileObj->fgets();
            if (!empty($line)) {
                $logs[] = $line;
            }
        }

        return $logs;
    }

    /**
     * 递归列出日志文件
     *
     * @param string $dir 目录路径
     * @param string $baseDir 基础目录
     * @param string $prefix 前缀
     */
    private function listLogFiles($dir, $baseDir, $prefix = '')
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $dir . '/' . $item;
            $relativePath = $prefix . $item;

            if (is_dir($fullPath)) {
                echo "  📁 $relativePath/\n";
                $this->listLogFiles($fullPath, $baseDir, $relativePath . '/');
            } elseif (pathinfo($item, PATHINFO_EXTENSION) === 'log') {
                $size = filesize($fullPath);
                $formattedSize = $this->formatFileSize($size);
                $mtime = date('Y-m-d H:i:s', filemtime($fullPath));
                echo "  📄 $relativePath ($formattedSize, $mtime)\n";
            }
        }
    }

    /**
     * 递归清理过期日志文件
     *
     * @param string $dir 目录路径
     * @param int $cutoffTime 截止时间
     * @param int &$deletedCount 删除计数
     */
    private function clearOldLogFiles($dir, $cutoffTime, &$deletedCount)
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $dir . '/' . $item;

            if (is_dir($fullPath)) {
                $this->clearOldLogFiles($fullPath, $cutoffTime, $deletedCount);
                // 如果目录为空，删除目录
                if (count(scandir($fullPath)) === 2) {
                    rmdir($fullPath);
                }
            } elseif (pathinfo($item, PATHINFO_EXTENSION) === 'log') {
                if (filemtime($fullPath) < $cutoffTime) {
                    unlink($fullPath);
                    $deletedCount++;
                }
            }
        }
    }

    /**
     * 格式化文件大小
     *
     * @param int $size 文件大小（字节）
     * @return string 格式化后的大小
     */
    private function formatFileSize($size)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
}
