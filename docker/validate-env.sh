#!/bin/bash

# PVM-Mirror 环境变量验证脚本
# 用于验证环境变量配置是否正确

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 计数器
TOTAL_CHECKS=0
PASSED_CHECKS=0
FAILED_CHECKS=0
WARNING_CHECKS=0

# 检查函数
check_env_var() {
    local var_name="$1"
    local expected_type="$2"
    local required="$3"
    local description="$4"
    
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    
    local value="${!var_name}"
    
    if [[ -z "$value" ]]; then
        if [[ "$required" == "true" ]]; then
            echo -e "${RED}❌ FAIL${NC} $var_name: 必需的环境变量未设置"
            echo "   描述: $description"
            FAILED_CHECKS=$((FAILED_CHECKS + 1))
            return 1
        else
            echo -e "${YELLOW}⚠️  WARN${NC} $var_name: 可选环境变量未设置（将使用默认值）"
            echo "   描述: $description"
            WARNING_CHECKS=$((WARNING_CHECKS + 1))
            return 0
        fi
    fi
    
    # 类型验证
    case "$expected_type" in
        "integer")
            if ! [[ "$value" =~ ^[0-9]+$ ]]; then
                echo -e "${RED}❌ FAIL${NC} $var_name: 期望整数，实际值: $value"
                FAILED_CHECKS=$((FAILED_CHECKS + 1))
                return 1
            fi
            ;;
        "boolean")
            if [[ "$value" != "true" && "$value" != "false" ]]; then
                echo -e "${RED}❌ FAIL${NC} $var_name: 期望布尔值(true/false)，实际值: $value"
                FAILED_CHECKS=$((FAILED_CHECKS + 1))
                return 1
            fi
            ;;
        "port")
            if ! [[ "$value" =~ ^[0-9]+$ ]] || [[ "$value" -lt 1 ]] || [[ "$value" -gt 65535 ]]; then
                echo -e "${RED}❌ FAIL${NC} $var_name: 期望有效端口号(1-65535)，实际值: $value"
                FAILED_CHECKS=$((FAILED_CHECKS + 1))
                return 1
            fi
            ;;
        "log_level")
            if [[ "$value" != "debug" && "$value" != "info" && "$value" != "warning" && "$value" != "error" ]]; then
                echo -e "${RED}❌ FAIL${NC} $var_name: 期望日志级别(debug/info/warning/error)，实际值: $value"
                FAILED_CHECKS=$((FAILED_CHECKS + 1))
                return 1
            fi
            ;;
        "environment")
            if [[ "$value" != "development" && "$value" != "production" && "$value" != "testing" ]]; then
                echo -e "${YELLOW}⚠️  WARN${NC} $var_name: 推荐环境值(development/production/testing)，实际值: $value"
                WARNING_CHECKS=$((WARNING_CHECKS + 1))
                return 0
            fi
            ;;
        "url")
            if ! [[ "$value" =~ ^https?:// ]]; then
                echo -e "${RED}❌ FAIL${NC} $var_name: 期望有效URL，实际值: $value"
                FAILED_CHECKS=$((FAILED_CHECKS + 1))
                return 1
            fi
            ;;
        "path")
            if [[ ! -d "$value" && ! -f "$value" ]]; then
                echo -e "${YELLOW}⚠️  WARN${NC} $var_name: 路径不存在，实际值: $value"
                WARNING_CHECKS=$((WARNING_CHECKS + 1))
                return 0
            fi
            ;;
    esac
    
    echo -e "${GREEN}✅ PASS${NC} $var_name: $value"
    echo "   描述: $description"
    PASSED_CHECKS=$((PASSED_CHECKS + 1))
    return 0
}

# 显示标题
echo "=============================================="
echo "  PVM-Mirror 环境变量验证"
echo "=============================================="
echo

# 加载.env文件（如果存在）
if [[ -f ".env" ]]; then
    echo "📁 加载 .env 文件..."
    set -a
    source .env
    set +a
    echo
fi

# 基础配置检查
echo "🔧 基础配置:"
check_env_var "PVM_MIRROR_ENV" "environment" "false" "运行环境"
check_env_var "PVM_MIRROR_DEBUG" "boolean" "false" "调试模式"
echo

# 目录配置检查
echo "📁 目录配置:"
check_env_var "PVM_MIRROR_DATA_DIR" "string" "false" "数据目录路径"
check_env_var "PVM_MIRROR_LOG_DIR" "string" "false" "日志目录路径"
check_env_var "PVM_MIRROR_CACHE_DIR" "string" "false" "缓存目录路径"
echo

# 日志配置检查
echo "📝 日志配置:"
check_env_var "PVM_MIRROR_LOG_LEVEL" "log_level" "false" "日志级别"
echo

# 服务器配置检查
echo "🌐 服务器配置:"
check_env_var "PVM_MIRROR_HOST" "string" "false" "监听主机"
check_env_var "PVM_MIRROR_PORT" "port" "false" "监听端口"
check_env_var "PVM_MIRROR_PUBLIC_URL" "url" "false" "公开URL"
check_env_var "PVM_MIRROR_MAX_CONNECTIONS" "integer" "false" "最大并发连接数"
check_env_var "PVM_MIRROR_TIMEOUT" "integer" "false" "请求超时时间"
echo

# HTTPS配置检查
echo "🔒 HTTPS配置:"
check_env_var "PVM_MIRROR_ENABLE_HTTPS" "boolean" "false" "是否启用HTTPS"
check_env_var "PVM_MIRROR_SSL_CERT" "string" "false" "SSL证书路径"
check_env_var "PVM_MIRROR_SSL_KEY" "string" "false" "SSL密钥路径"
echo

# 缓存配置检查
echo "💾 缓存配置:"
check_env_var "PVM_MIRROR_CACHE_SIZE" "integer" "false" "缓存最大大小"
check_env_var "PVM_MIRROR_CACHE_TTL" "integer" "false" "缓存TTL"
echo

# 同步配置检查
echo "🔄 同步配置:"
check_env_var "PVM_MIRROR_SYNC_INTERVAL" "integer" "false" "同步间隔"
check_env_var "PVM_MIRROR_MAX_RETRIES" "integer" "false" "最大重试次数"
check_env_var "PVM_MIRROR_RETRY_INTERVAL" "integer" "false" "重试间隔"
echo

# 显示结果
echo "=============================================="
echo "  验证结果"
echo "=============================================="
echo "总检查项: $TOTAL_CHECKS"
echo -e "通过: ${GREEN}$PASSED_CHECKS${NC}"
echo -e "警告: ${YELLOW}$WARNING_CHECKS${NC}"
echo -e "失败: ${RED}$FAILED_CHECKS${NC}"
echo

if [[ $FAILED_CHECKS -eq 0 ]]; then
    echo -e "${GREEN}🎉 所有必需的环境变量验证通过！${NC}"
    if [[ $WARNING_CHECKS -gt 0 ]]; then
        echo -e "${YELLOW}⚠️  有 $WARNING_CHECKS 个警告，请检查配置${NC}"
    fi
    exit 0
else
    echo -e "${RED}❌ 有 $FAILED_CHECKS 个环境变量验证失败${NC}"
    echo "请修复上述问题后重新运行验证"
    exit 1
fi
