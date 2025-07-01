<?php

namespace Mirror\Security;

use Mirror\Config\ConfigManager;

/**
 * 访问控制类
 *
 * 用于控制对镜像服务的访问权限
 */
class AccessControl
{
    /**
     * 配置管理器
     *
     * @var ConfigManager
     */
    private $configManager;

    /**
     * 安全配置
     *
     * @var array
     */
    private $securityConfig;

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
        $this->configManager = new ConfigManager();
        $this->securityConfig = $this->configManager->getSecurityConfig();
        $this->accessLog = new AccessLog();
    }

    /**
     * 检查访问权限
     *
     * @param string $method 请求方法
     * @param string $uri 请求URI
     * @return bool 是否允许访问
     */
    public function checkAccess($method = '', $uri = '')
    {
        // 如果未提供请求方法，则从服务器变量中获取
        if (empty($method) && isset($_SERVER['REQUEST_METHOD'])) {
            $method = $_SERVER['REQUEST_METHOD'];
        }

        // 如果未提供请求URI，则从服务器变量中获取
        if (empty($uri) && isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        }

        // 获取客户端IP
        $clientIp = $this->getClientIp();

        // 如果未启用访问控制，则允许所有访问
        if (!$this->isAccessControlEnabled()) {
            // 记录访问日志
            $this->accessLog->log($clientIp, $method, $uri, 200);
            return true;
        }

        // 检查IP白名单
        if (!$this->checkIpWhitelist()) {
            // 记录访问被拒绝
            $this->accessLog->logDenied($clientIp, $method, $uri, 'IP not in whitelist');
            return false;
        }

        // 检查基本认证
        if (!$this->checkBasicAuth()) {
            // 记录访问被拒绝
            $this->accessLog->logDenied($clientIp, $method, $uri, 'Authentication failed');
            return false;
        }

        // 记录访问日志
        $this->accessLog->log($clientIp, $method, $uri, 200);
        return true;
    }

    /**
     * 检查基本认证
     *
     * @return bool 是否通过认证
     */
    public function checkBasicAuth()
    {
        // 如果未启用基本认证，则直接返回成功
        if (!isset($this->securityConfig['enable_basic_auth']) ||
            !$this->securityConfig['enable_basic_auth'] ||
            empty($this->securityConfig['auth_users'])) {
            return true;
        }

        // 获取认证信息
        $username = '';
        $password = '';

        // 检查是否提供了认证信息
        if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            // 如果使用了代理，可能需要从HTTP_AUTHORIZATION中获取认证信息
            if (strpos(strtolower($_SERVER['HTTP_AUTHORIZATION']), 'basic') === 0) {
                $authValue = substr($_SERVER['HTTP_AUTHORIZATION'], 6);
                $authData = base64_decode($authValue);
                list($username, $password) = explode(':', $authData, 2);
            }
        }

        // 如果没有提供认证信息，则返回失败
        if (empty($username) || empty($password)) {
            return false;
        }

        // 检查用户名和密码是否匹配
        $users = $this->securityConfig['auth_users'];
        if (isset($users[$username]) && $this->verifyPassword($password, $users[$username])) {
            return true;
        }

        return false;
    }

    /**
     * 验证密码
     *
     * @param string $password 输入的密码
     * @param string $storedPassword 存储的密码（可能是哈希值）
     * @return bool 是否匹配
     */
    private function verifyPassword($password, $storedPassword)
    {
        // 如果存储的密码以$开头，则可能是哈希值
        if (strpos($storedPassword, '$') === 0) {
            return password_verify($password, $storedPassword);
        }

        // 否则直接比较
        return $password === $storedPassword;
    }

    /**
     * 检查是否启用访问控制
     *
     * @return bool
     */
    public function isAccessControlEnabled()
    {
        return isset($this->securityConfig['enable_access_control']) &&
               $this->securityConfig['enable_access_control'] === true;
    }

    /**
     * 检查IP白名单
     *
     * @return bool 是否允许访问
     */
    public function checkIpWhitelist()
    {
        // 如果未启用IP白名单或白名单为空，则允许所有IP
        if (!isset($this->securityConfig['enable_ip_whitelist']) ||
            !$this->securityConfig['enable_ip_whitelist'] ||
            empty($this->securityConfig['allowed_ips'])) {
            return true;
        }

        // 获取客户端IP
        $clientIp = $this->getClientIp();

        // 检查IP是否在白名单中
        if (in_array($clientIp, $this->securityConfig['allowed_ips'])) {
            return true;
        }

        // 检查IP是否在CIDR范围内
        foreach ($this->securityConfig['allowed_ips'] as $allowedIp) {
            // 如果是CIDR表示法
            if (strpos($allowedIp, '/') !== false) {
                if ($this->ipInCidr($clientIp, $allowedIp)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 检查IP是否在CIDR范围内
     *
     * @param string $ip 要检查的IP
     * @param string $cidr CIDR表示法的IP范围
     * @return bool 是否在范围内
     */
    private function ipInCidr($ip, $cidr)
    {
        // 分割CIDR表示法
        list($subnet, $bits) = explode('/', $cidr);

        // 将IP地址转换为长整型
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);

        // 如果转换失败，则返回false
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        // 计算子网掩码
        $mask = -1 << (32 - $bits);

        // 应用子网掩码
        $subnetLong &= $mask;

        // 检查IP是否在子网内
        return ($ipLong & $mask) === $subnetLong;
    }

    /**
     * 获取客户端IP
     *
     * @return string
     */
    public function getClientIp()
    {
        // 尝试从各种可能的服务器变量中获取客户端IP
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // HTTP_X_FORWARDED_FOR可能包含多个IP，取第一个
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            return $_SERVER['HTTP_FORWARDED'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return '0.0.0.0';
    }

    /**
     * 处理访问被拒绝
     *
     * @param string $method 请求方法
     * @param string $uri 请求URI
     * @param string $reason 拒绝原因
     */
    public function handleAccessDenied($method = '', $uri = '', $reason = '')
    {
        // 如果未提供请求方法，则从服务器变量中获取
        if (empty($method) && isset($_SERVER['REQUEST_METHOD'])) {
            $method = $_SERVER['REQUEST_METHOD'];
        }

        // 如果未提供请求URI，则从服务器变量中获取
        if (empty($uri) && isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        }

        // 获取客户端IP
        $clientIp = $this->getClientIp();

        // 检查是否是因为基本认证失败
        $isAuthFailure = false;
        if (empty($reason)) {
            // 检查是否启用了基本认证
            if (isset($this->securityConfig['enable_basic_auth']) &&
                $this->securityConfig['enable_basic_auth'] &&
                !empty($this->securityConfig['auth_users'])) {

                // 检查是否提供了认证信息
                $hasAuthInfo = isset($_SERVER['PHP_AUTH_USER']) ||
                              (isset($_SERVER['HTTP_AUTHORIZATION']) &&
                               strpos(strtolower($_SERVER['HTTP_AUTHORIZATION']), 'basic') === 0);

                if (!$hasAuthInfo) {
                    $isAuthFailure = true;
                    $reason = 'Authentication required';
                } else {
                    $reason = 'Authentication failed';
                }
            } else {
                $reason = 'IP not in whitelist';
            }
        }

        // 记录访问被拒绝
        $this->accessLog->logDenied($clientIp, $method, $uri, $reason);

        // 如果是认证失败，则发送401响应
        if ($isAuthFailure) {
            header('HTTP/1.1 401 Unauthorized');
            header('WWW-Authenticate: Basic realm="PVM Mirror"');
            header('Content-Type: text/html; charset=utf-8');

            echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>401 Unauthorized</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #d9534f;
        }
    </style>
</head>
<body>
    <h1>401 Unauthorized</h1>
    <p>需要认证才能访问此资源。</p>
    <p>您的IP地址: ' . $clientIp . '</p>
</body>
</html>';
        } else {
            // 否则发送403响应
            header('HTTP/1.0 403 Forbidden');
            header('Content-Type: text/html; charset=utf-8');

            echo '<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 Forbidden</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #d9534f;
        }
    </style>
</head>
<body>
    <h1>403 Forbidden</h1>
    <p>您没有权限访问此资源。</p>
    <p>您的IP地址: ' . $clientIp . '</p>
    <p>原因: ' . htmlspecialchars($reason) . '</p>
    <p>如果您认为这是一个错误，请联系系统管理员。</p>
</body>
</html>';
        }

        exit;
    }

    /**
     * 添加IP到白名单
     *
     * @param string $ip IP地址或CIDR表示法
     * @return bool 是否成功
     */
    public function addIpToWhitelist($ip)
    {
        // 验证IP格式
        if (strpos($ip, '/') !== false) {
            // CIDR表示法
            list($subnet, $bits) = explode('/', $ip);

            // 验证子网地址
            if (!filter_var($subnet, FILTER_VALIDATE_IP)) {
                return false;
            }

            // 验证位数
            if (!is_numeric($bits) || $bits < 0 || $bits > 32) {
                return false;
            }
        } else {
            // 普通IP地址
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                return false;
            }
        }

        // 获取当前运行时配置
        $runtimeConfig = $this->configManager->getRuntimeConfig();

        // 确保安全配置存在
        if (!isset($runtimeConfig['security'])) {
            $runtimeConfig['security'] = [];
        }

        // 确保allowed_ips数组存在
        if (!isset($runtimeConfig['security']['allowed_ips'])) {
            $runtimeConfig['security']['allowed_ips'] = [];
        }

        // 如果IP已经在白名单中，则返回成功
        if (in_array($ip, $runtimeConfig['security']['allowed_ips'])) {
            return true;
        }

        // 添加IP到白名单
        $runtimeConfig['security']['allowed_ips'][] = $ip;

        // 保存配置
        return $this->configManager->saveRuntimeConfig($runtimeConfig);
    }

    /**
     * 从白名单中移除IP
     *
     * @param string $ip IP地址
     * @return bool 是否成功
     */
    public function removeIpFromWhitelist($ip)
    {
        // 获取当前运行时配置
        $runtimeConfig = $this->configManager->getRuntimeConfig();

        // 如果安全配置或白名单不存在，则返回成功
        if (!isset($runtimeConfig['security']) ||
            !isset($runtimeConfig['security']['allowed_ips'])) {
            return true;
        }

        // 查找IP在白名单中的位置
        $key = array_search($ip, $runtimeConfig['security']['allowed_ips']);

        // 如果IP不在白名单中，则返回成功
        if ($key === false) {
            return true;
        }

        // 从白名单中移除IP
        unset($runtimeConfig['security']['allowed_ips'][$key]);

        // 重新索引数组
        $runtimeConfig['security']['allowed_ips'] = array_values($runtimeConfig['security']['allowed_ips']);

        // 保存配置
        return $this->configManager->saveRuntimeConfig($runtimeConfig);
    }

    /**
     * 启用或禁用访问控制
     *
     * @param bool $enabled 是否启用
     * @return bool 是否成功
     */
    public function setAccessControlEnabled($enabled)
    {
        // 获取当前运行时配置
        $runtimeConfig = $this->configManager->getRuntimeConfig();

        // 确保安全配置存在
        if (!isset($runtimeConfig['security'])) {
            $runtimeConfig['security'] = [];
        }

        // 设置访问控制状态
        $runtimeConfig['security']['enable_access_control'] = (bool)$enabled;

        // 保存配置
        return $this->configManager->saveRuntimeConfig($runtimeConfig);
    }

    /**
     * 启用或禁用IP白名单
     *
     * @param bool $enabled 是否启用
     * @return bool 是否成功
     */
    public function setIpWhitelistEnabled($enabled)
    {
        // 获取当前运行时配置
        $runtimeConfig = $this->configManager->getRuntimeConfig();

        // 确保安全配置存在
        if (!isset($runtimeConfig['security'])) {
            $runtimeConfig['security'] = [];
        }

        // 设置IP白名单状态
        $runtimeConfig['security']['enable_ip_whitelist'] = (bool)$enabled;

        // 保存配置
        return $this->configManager->saveRuntimeConfig($runtimeConfig);
    }

    /**
     * 启用或禁用基本认证
     *
     * @param bool $enabled 是否启用
     * @return bool 是否成功
     */
    public function setBasicAuthEnabled($enabled)
    {
        // 获取当前运行时配置
        $runtimeConfig = $this->configManager->getRuntimeConfig();

        // 确保安全配置存在
        if (!isset($runtimeConfig['security'])) {
            $runtimeConfig['security'] = [];
        }

        // 设置基本认证状态
        $runtimeConfig['security']['enable_basic_auth'] = (bool)$enabled;

        // 保存配置
        return $this->configManager->saveRuntimeConfig($runtimeConfig);
    }

    /**
     * 添加用户
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param bool $hashPassword 是否哈希密码
     * @return bool 是否成功
     */
    public function addUser($username, $password, $hashPassword = true)
    {
        // 验证用户名
        if (empty($username)) {
            return false;
        }

        // 验证密码
        if (empty($password)) {
            return false;
        }

        // 获取当前运行时配置
        $runtimeConfig = $this->configManager->getRuntimeConfig();

        // 确保安全配置存在
        if (!isset($runtimeConfig['security'])) {
            $runtimeConfig['security'] = [];
        }

        // 确保auth_users数组存在
        if (!isset($runtimeConfig['security']['auth_users'])) {
            $runtimeConfig['security']['auth_users'] = [];
        }

        // 如果需要哈希密码
        if ($hashPassword) {
            $password = password_hash($password, PASSWORD_DEFAULT);
        }

        // 添加用户
        $runtimeConfig['security']['auth_users'][$username] = $password;

        // 保存配置
        return $this->configManager->saveRuntimeConfig($runtimeConfig);
    }

    /**
     * 移除用户
     *
     * @param string $username 用户名
     * @return bool 是否成功
     */
    public function removeUser($username)
    {
        // 获取当前运行时配置
        $runtimeConfig = $this->configManager->getRuntimeConfig();

        // 如果安全配置或用户列表不存在，则返回成功
        if (!isset($runtimeConfig['security']) ||
            !isset($runtimeConfig['security']['auth_users'])) {
            return true;
        }

        // 如果用户不存在，则返回成功
        if (!isset($runtimeConfig['security']['auth_users'][$username])) {
            return true;
        }

        // 移除用户
        unset($runtimeConfig['security']['auth_users'][$username]);

        // 保存配置
        return $this->configManager->saveRuntimeConfig($runtimeConfig);
    }

    /**
     * 获取用户列表
     *
     * @return array 用户列表
     */
    public function getUsers()
    {
        // 如果安全配置或用户列表不存在，则返回空数组
        if (!isset($this->securityConfig['auth_users'])) {
            return [];
        }

        // 返回用户列表（只返回用户名，不返回密码）
        return array_keys($this->securityConfig['auth_users']);
    }
}
