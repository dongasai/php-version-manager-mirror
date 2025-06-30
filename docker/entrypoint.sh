#!/bin/bash

# PVM-Mirror Docker 容器启动脚本
# 负责环境变量处理和服务启动

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 日志函数
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_debug() {
    if [[ "${PVM_MIRROR_DEBUG}" == "true" ]]; then
        echo -e "${BLUE}[DEBUG]${NC} $1"
    fi
}

# 显示启动信息
show_startup_info() {
    echo "=============================================="
    echo "  PVM-Mirror Docker Container"
    echo "=============================================="
    echo "Environment: ${PVM_MIRROR_ENV:-production}"
    echo "Debug Mode: ${PVM_MIRROR_DEBUG:-false}"
    echo "Host: ${PVM_MIRROR_HOST:-0.0.0.0}"
    echo "Port: ${PVM_MIRROR_PORT:-34403}"
    echo "Public URL: ${PVM_MIRROR_PUBLIC_URL:-http://localhost:34403}"
    echo "Log Level: ${PVM_MIRROR_LOG_LEVEL:-info}"
    echo "Data Dir: ${PVM_MIRROR_DATA_DIR:-/app/data}"
    echo "Log Dir: ${PVM_MIRROR_LOG_DIR:-/app/logs}"
    echo "Cache Dir: ${PVM_MIRROR_CACHE_DIR:-/app/cache}"
    echo "=============================================="
}

# 检查和创建目录
ensure_directories() {
    local dirs=(
        "${PVM_MIRROR_DATA_DIR:-/app/data}"
        "${PVM_MIRROR_LOG_DIR:-/app/logs}"
        "${PVM_MIRROR_CACHE_DIR:-/app/cache}"
    )

    for dir in "${dirs[@]}"; do
        if [[ ! -d "$dir" ]]; then
            log_info "Creating directory: $dir"
            mkdir -p "$dir" || {
                log_error "Failed to create directory: $dir"
                exit 1
            }
        fi
        
        # 确保目录权限正确
        if [[ ! -w "$dir" ]]; then
            log_warn "Directory not writable: $dir"
        fi
    done
}

# 验证环境变量
validate_environment() {
    # 验证端口号
    if [[ -n "${PVM_MIRROR_PORT}" ]]; then
        if ! [[ "${PVM_MIRROR_PORT}" =~ ^[0-9]+$ ]] || [[ "${PVM_MIRROR_PORT}" -lt 1 ]] || [[ "${PVM_MIRROR_PORT}" -gt 65535 ]]; then
            log_error "Invalid port number: ${PVM_MIRROR_PORT}"
            exit 1
        fi
    fi

    # 验证日志级别
    if [[ -n "${PVM_MIRROR_LOG_LEVEL}" ]]; then
        case "${PVM_MIRROR_LOG_LEVEL}" in
            debug|info|warning|error) ;;
            *) 
                log_error "Invalid log level: ${PVM_MIRROR_LOG_LEVEL}. Must be one of: debug, info, warning, error"
                exit 1
                ;;
        esac
    fi

    # 验证环境类型
    if [[ -n "${PVM_MIRROR_ENV}" ]]; then
        case "${PVM_MIRROR_ENV}" in
            development|production|testing) ;;
            *) 
                log_warn "Unknown environment: ${PVM_MIRROR_ENV}. Recommended values: development, production, testing"
                ;;
        esac
    fi
}

# 加载.env文件（如果存在）
load_env_file() {
    local env_file="/app/.env"
    if [[ -f "$env_file" ]]; then
        log_info "Loading environment variables from $env_file"
        set -a  # 自动导出变量
        source "$env_file"
        set +a
    fi
}

# 设置开发环境特定配置
setup_development_env() {
    if [[ "${PVM_MIRROR_ENV}" == "development" ]]; then
        log_info "Setting up development environment"
        
        # 开发环境默认启用调试
        export PVM_MIRROR_DEBUG=${PVM_MIRROR_DEBUG:-true}
        export PVM_MIRROR_LOG_LEVEL=${PVM_MIRROR_LOG_LEVEL:-debug}
        
        # 减少缓存时间以便测试
        export PVM_MIRROR_CACHE_TTL=${PVM_MIRROR_CACHE_TTL:-60}
        
        # 增加同步频率以便测试
        export PVM_MIRROR_SYNC_INTERVAL=${PVM_MIRROR_SYNC_INTERVAL:-1}
    fi
}

# 显示环境变量（调试用）
show_environment() {
    if [[ "${PVM_MIRROR_DEBUG}" == "true" ]]; then
        log_debug "Environment variables:"
        env | grep "PVM_MIRROR_" | sort
    fi
}

# 检查依赖
check_dependencies() {
    # 检查PHP
    if ! command -v php >/dev/null 2>&1; then
        log_error "PHP is not installed or not in PATH"
        exit 1
    fi

    # 检查pvm-mirror脚本
    if [[ ! -x "/app/bin/pvm-mirror" ]]; then
        log_error "pvm-mirror script not found or not executable"
        exit 1
    fi

    # 检查配置文件
    if [[ ! -f "/app/config/runtime.php" ]]; then
        log_error "Runtime configuration file not found"
        exit 1
    fi
}

# 主函数
main() {
    # 加载环境文件
    load_env_file
    
    # 显示启动信息
    show_startup_info
    
    # 验证环境变量
    validate_environment
    
    # 设置开发环境
    setup_development_env
    
    # 显示环境变量（调试模式）
    show_environment
    
    # 确保目录存在
    ensure_directories
    
    # 检查依赖
    check_dependencies
    
    log_info "Starting PVM-Mirror with arguments: $@"
    
    # 启动pvm-mirror
    exec /app/bin/pvm-mirror "$@"
}

# 执行主函数
main "$@"
