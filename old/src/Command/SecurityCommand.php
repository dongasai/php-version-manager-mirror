<?php

namespace Mirror\Command;

use Mirror\Security\AccessControl;
use Mirror\Security\AccessLog;

/**
 * 安全命令类
 */
class SecurityCommand extends AbstractCommand
{
    /**
     * 访问控制
     *
     * @var AccessControl
     */
    private $accessControl;

    /**
     * 访问日志
     *
     * @var AccessLog
     */
    private $accessLog;

    /**
     * 构造函数
     */
    public function __construct()
    {
        parent::__construct('security', '管理安全设置');
        $this->accessControl = new AccessControl();
        $this->accessLog = new AccessLog();
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
            case 'status':
                return $this->showStatus();

            case 'enable':
                return $this->enableAccessControl();

            case 'disable':
                return $this->disableAccessControl();

            case 'whitelist':
                if (count($args) < 2) {
                    echo "错误: 缺少子命令\n";
                    return $this->showHelp();
                }
                return $this->handleWhitelist(array_slice($args, 1));

            case 'log':
                if (count($args) < 2) {
                    echo "错误: 缺少子命令\n";
                    return $this->showHelp();
                }
                return $this->handleLog(array_slice($args, 1));

            case 'auth':
                if (count($args) < 2) {
                    echo "错误: 缺少子命令\n";
                    return $this->showHelp();
                }
                return $this->handleAuth(array_slice($args, 1));

            case 'help':
                return $this->showHelp();

            default:
                echo "未知操作: $action\n";
                return $this->showHelp();
        }
    }

    /**
     * 显示安全状态
     *
     * @return int 退出代码
     */
    private function showStatus()
    {
        // 获取配置
        $configManager = new \Mirror\Config\ConfigManager();
        $securityConfig = $configManager->getSecurityConfig();

        echo "安全设置状态:\n";
        echo "访问控制: " . ($securityConfig['enable_access_control'] ? '启用' : '禁用') . "\n";
        echo "IP白名单: " . ($securityConfig['enable_ip_whitelist'] ?? false ? '启用' : '禁用') . "\n";
        echo "基本认证: " . ($securityConfig['enable_basic_auth'] ?? false ? '启用' : '禁用') . "\n";

        if (isset($securityConfig['allowed_ips']) && !empty($securityConfig['allowed_ips'])) {
            echo "允许的IP地址:\n";
            foreach ($securityConfig['allowed_ips'] as $ip) {
                echo "  - $ip\n";
            }
        } else {
            echo "允许的IP地址: 无\n";
        }

        if (isset($securityConfig['auth_users']) && !empty($securityConfig['auth_users'])) {
            echo "认证用户:\n";
            foreach (array_keys($securityConfig['auth_users']) as $username) {
                echo "  - $username\n";
            }
        } else {
            echo "认证用户: 无\n";
        }

        return 0;
    }

    /**
     * 启用访问控制
     *
     * @return int 退出代码
     */
    private function enableAccessControl()
    {
        if ($this->accessControl->setAccessControlEnabled(true)) {
            echo "访问控制已启用\n";
            return 0;
        } else {
            echo "启用访问控制失败\n";
            return 1;
        }
    }

    /**
     * 禁用访问控制
     *
     * @return int 退出代码
     */
    private function disableAccessControl()
    {
        if ($this->accessControl->setAccessControlEnabled(false)) {
            echo "访问控制已禁用\n";
            return 0;
        } else {
            echo "禁用访问控制失败\n";
            return 1;
        }
    }

    /**
     * 处理白名单操作
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    private function handleWhitelist(array $args)
    {
        if (empty($args)) {
            echo "错误: 缺少白名单操作\n";
            return $this->showHelp();
        }

        $action = $args[0];

        switch ($action) {
            case 'enable':
                return $this->enableWhitelist();

            case 'disable':
                return $this->disableWhitelist();

            case 'add':
                if (count($args) < 2) {
                    echo "错误: 缺少IP地址\n";
                    return 1;
                }
                return $this->addToWhitelist($args[1]);

            case 'remove':
                if (count($args) < 2) {
                    echo "错误: 缺少IP地址\n";
                    return 1;
                }
                return $this->removeFromWhitelist($args[1]);

            case 'list':
                return $this->listWhitelist();

            default:
                echo "未知的白名单操作: $action\n";
                return $this->showHelp();
        }
    }

    /**
     * 启用IP白名单
     *
     * @return int 退出代码
     */
    private function enableWhitelist()
    {
        if ($this->accessControl->setIpWhitelistEnabled(true)) {
            echo "IP白名单已启用\n";
            return 0;
        } else {
            echo "启用IP白名单失败\n";
            return 1;
        }
    }

    /**
     * 禁用IP白名单
     *
     * @return int 退出代码
     */
    private function disableWhitelist()
    {
        if ($this->accessControl->setIpWhitelistEnabled(false)) {
            echo "IP白名单已禁用\n";
            return 0;
        } else {
            echo "禁用IP白名单失败\n";
            return 1;
        }
    }

    /**
     * 添加IP到白名单
     *
     * @param string $ip IP地址
     * @return int 退出代码
     */
    private function addToWhitelist($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            echo "错误: 无效的IP地址: $ip\n";
            return 1;
        }

        if ($this->accessControl->addIpToWhitelist($ip)) {
            echo "IP地址 $ip 已添加到白名单\n";
            return 0;
        } else {
            echo "添加IP地址到白名单失败\n";
            return 1;
        }
    }

    /**
     * 从白名单中移除IP
     *
     * @param string $ip IP地址
     * @return int 退出代码
     */
    private function removeFromWhitelist($ip)
    {
        if ($this->accessControl->removeIpFromWhitelist($ip)) {
            echo "IP地址 $ip 已从白名单中移除\n";
            return 0;
        } else {
            echo "从白名单中移除IP地址失败\n";
            return 1;
        }
    }

    /**
     * 列出白名单
     *
     * @return int 退出代码
     */
    private function listWhitelist()
    {
        // 获取配置
        $configManager = new \Mirror\Config\ConfigManager();
        $securityConfig = $configManager->getSecurityConfig();

        echo "IP白名单:\n";
        if (isset($securityConfig['allowed_ips']) && !empty($securityConfig['allowed_ips'])) {
            foreach ($securityConfig['allowed_ips'] as $ip) {
                echo "  - $ip\n";
            }
        } else {
            echo "  (空)\n";
        }

        return 0;
    }

    /**
     * 处理日志操作
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    private function handleLog(array $args)
    {
        if (empty($args)) {
            echo "错误: 缺少日志操作\n";
            return $this->showHelp();
        }

        $action = $args[0];

        switch ($action) {
            case 'show':
                return $this->showLogs(isset($args[1]) ? (int)$args[1] : 10);

            case 'clear':
                return $this->clearLogs();

            case 'path':
                return $this->showLogPath();

            default:
                echo "未知的日志操作: $action\n";
                return $this->showHelp();
        }
    }

    /**
     * 显示访问日志
     *
     * @param int $lines 行数
     * @return int 退出代码
     */
    private function showLogs($lines = 10)
    {
        $logs = $this->accessLog->getRecentLogs($lines);

        if (empty($logs)) {
            echo "访问日志为空\n";
            return 0;
        }

        echo "最近 " . count($logs) . " 条访问日志:\n";
        foreach ($logs as $log) {
            echo $log;
        }

        return 0;
    }

    /**
     * 清空访问日志
     *
     * @return int 退出代码
     */
    private function clearLogs()
    {
        if ($this->accessLog->clearLogs()) {
            echo "访问日志已清空\n";
            return 0;
        } else {
            echo "清空访问日志失败\n";
            return 1;
        }
    }

    /**
     * 显示日志文件路径
     *
     * @return int 退出代码
     */
    private function showLogPath()
    {
        echo "访问日志文件路径: " . $this->accessLog->getLogFile() . "\n";
        return 0;
    }

    /**
     * 处理认证操作
     *
     * @param array $args 命令参数
     * @return int 退出代码
     */
    private function handleAuth(array $args)
    {
        if (empty($args)) {
            echo "错误: 缺少认证操作\n";
            return $this->showHelp();
        }

        $action = $args[0];

        switch ($action) {
            case 'enable':
                return $this->enableBasicAuth();

            case 'disable':
                return $this->disableBasicAuth();

            case 'add':
                if (count($args) < 3) {
                    echo "错误: 缺少用户名或密码\n";
                    return 1;
                }
                return $this->addUser($args[1], $args[2]);

            case 'remove':
                if (count($args) < 2) {
                    echo "错误: 缺少用户名\n";
                    return 1;
                }
                return $this->removeUser($args[1]);

            case 'list':
                return $this->listUsers();

            default:
                echo "未知的认证操作: $action\n";
                return $this->showHelp();
        }
    }

    /**
     * 启用基本认证
     *
     * @return int 退出代码
     */
    private function enableBasicAuth()
    {
        if ($this->accessControl->setBasicAuthEnabled(true)) {
            echo "基本认证已启用\n";
            return 0;
        } else {
            echo "启用基本认证失败\n";
            return 1;
        }
    }

    /**
     * 禁用基本认证
     *
     * @return int 退出代码
     */
    private function disableBasicAuth()
    {
        if ($this->accessControl->setBasicAuthEnabled(false)) {
            echo "基本认证已禁用\n";
            return 0;
        } else {
            echo "禁用基本认证失败\n";
            return 1;
        }
    }

    /**
     * 添加用户
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @return int 退出代码
     */
    private function addUser($username, $password)
    {
        if ($this->accessControl->addUser($username, $password)) {
            echo "用户 $username 已添加\n";
            return 0;
        } else {
            echo "添加用户失败\n";
            return 1;
        }
    }

    /**
     * 移除用户
     *
     * @param string $username 用户名
     * @return int 退出代码
     */
    private function removeUser($username)
    {
        if ($this->accessControl->removeUser($username)) {
            echo "用户 $username 已移除\n";
            return 0;
        } else {
            echo "移除用户失败\n";
            return 1;
        }
    }

    /**
     * 列出用户
     *
     * @return int 退出代码
     */
    private function listUsers()
    {
        $users = $this->accessControl->getUsers();

        if (empty($users)) {
            echo "用户列表为空\n";
            return 0;
        }

        echo "用户列表:\n";
        foreach ($users as $username) {
            echo "  - $username\n";
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
        echo "安全设置管理\n";
        echo "用法: pvm-mirror security <操作> [参数]\n\n";
        echo "可用操作:\n";
        echo "  status                显示安全设置状态\n";
        echo "  enable                启用访问控制\n";
        echo "  disable               禁用访问控制\n";
        echo "  whitelist enable      启用IP白名单\n";
        echo "  whitelist disable     禁用IP白名单\n";
        echo "  whitelist add <IP>    添加IP到白名单（支持CIDR格式，如192.168.1.0/24）\n";
        echo "  whitelist remove <IP> 从白名单中移除IP\n";
        echo "  whitelist list        列出白名单\n";
        echo "  auth enable           启用基本认证\n";
        echo "  auth disable          禁用基本认证\n";
        echo "  auth add <用户名> <密码> 添加用户\n";
        echo "  auth remove <用户名>   移除用户\n";
        echo "  auth list             列出用户\n";
        echo "  log show [行数]       显示访问日志\n";
        echo "  log clear             清空访问日志\n";
        echo "  log path              显示日志文件路径\n";
        echo "  help                  显示此帮助信息\n\n";
        echo "示例:\n";
        echo "  pvm-mirror security status\n";
        echo "  pvm-mirror security enable\n";
        echo "  pvm-mirror security whitelist enable\n";
        echo "  pvm-mirror security whitelist add 192.168.1.100\n";
        echo "  pvm-mirror security whitelist add 192.168.1.0/24\n";
        echo "  pvm-mirror security auth enable\n";
        echo "  pvm-mirror security auth add admin password123\n";
        echo "  pvm-mirror security auth list\n";
        echo "  pvm-mirror security log show 20\n";

        return 0;
    }
}
