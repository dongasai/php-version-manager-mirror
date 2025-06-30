#!/bin/bash

# PVM-Mirror Docker镜像管理脚本
# 用于本地构建、测试和推送Docker镜像

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 配置
REGISTRY="ghcr.io"
REPOSITORY="your-username/pvm"  # 需要替换为实际的仓库名
IMAGE_NAME="pvm-mirror"
DOCKERFILE="Dockerfile"
CONTEXT="../../"

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
    echo -e "${BLUE}[DEBUG]${NC} $1"
}

# 显示帮助信息
show_help() {
    cat << EOF
PVM-Mirror Docker镜像管理脚本

用法: $0 [命令] [选项]

命令:
  build [TAG]     构建Docker镜像
  test [TAG]      测试Docker镜像
  push [TAG]      推送Docker镜像到注册表
  run [TAG]       运行Docker容器
  clean           清理本地镜像和容器
  login           登录到容器注册表
  info            显示镜像信息
  help            显示此帮助信息

选项:
  --registry REG  指定容器注册表 (默认: $REGISTRY)
  --repo REPO     指定仓库名称 (默认: $REPOSITORY)
  --platform PLAT 指定构建平台 (默认: linux/amd64)
  --no-cache      不使用构建缓存
  --push          构建后自动推送
  --latest        同时标记为latest

示例:
  $0 build v1.0.0                    # 构建v1.0.0版本
  $0 build dev --push                # 构建dev版本并推送
  $0 test latest                      # 测试latest镜像
  $0 push v1.0.0 --latest           # 推送v1.0.0并标记为latest
  $0 run dev                         # 运行dev版本容器

环境变量:
  DOCKER_REGISTRY    容器注册表地址
  DOCKER_REPOSITORY  仓库名称
  DOCKER_USERNAME    用户名
  DOCKER_PASSWORD    密码或Token
EOF
}

# 解析命令行参数
parse_args() {
    COMMAND=""
    TAG="latest"
    PLATFORM="linux/amd64"
    NO_CACHE=""
    AUTO_PUSH=false
    TAG_LATEST=false
    
    while [[ $# -gt 0 ]]; do
        case $1 in
            build|test|push|run|clean|login|info|help)
                COMMAND="$1"
                shift
                ;;
            --registry)
                REGISTRY="$2"
                shift 2
                ;;
            --repo)
                REPOSITORY="$2"
                shift 2
                ;;
            --platform)
                PLATFORM="$2"
                shift 2
                ;;
            --no-cache)
                NO_CACHE="--no-cache"
                shift
                ;;
            --push)
                AUTO_PUSH=true
                shift
                ;;
            --latest)
                TAG_LATEST=true
                shift
                ;;
            -*)
                log_error "未知选项: $1"
                show_help
                exit 1
                ;;
            *)
                if [[ -z "$TAG" || "$TAG" == "latest" ]]; then
                    TAG="$1"
                fi
                shift
                ;;
        esac
    done
    
    # 使用环境变量覆盖默认值
    REGISTRY="${DOCKER_REGISTRY:-$REGISTRY}"
    REPOSITORY="${DOCKER_REPOSITORY:-$REPOSITORY}"
    
    # 构建完整镜像名称
    FULL_IMAGE_NAME="$REGISTRY/$REPOSITORY/$IMAGE_NAME"
}

# 检查Docker环境
check_docker() {
    if ! command -v docker >/dev/null 2>&1; then
        log_error "Docker未安装或不在PATH中"
        exit 1
    fi
    
    if ! docker info >/dev/null 2>&1; then
        log_error "Docker服务未运行或无权限访问"
        exit 1
    fi
}

# 构建镜像
build_image() {
    local tag="$1"
    local image_tag="$FULL_IMAGE_NAME:$tag"
    
    log_info "构建Docker镜像: $image_tag"
    log_info "平台: $PLATFORM"
    log_info "上下文: $CONTEXT"
    log_info "Dockerfile: $DOCKERFILE"
    
    # 构建命令
    local build_cmd="docker build"
    build_cmd="$build_cmd --platform $PLATFORM"
    build_cmd="$build_cmd -t $image_tag"
    build_cmd="$build_cmd -f $DOCKERFILE"
    build_cmd="$build_cmd $NO_CACHE"
    build_cmd="$build_cmd $CONTEXT"
    
    log_debug "执行命令: $build_cmd"
    
    if eval "$build_cmd"; then
        log_info "镜像构建成功: $image_tag"
        
        # 如果需要标记为latest
        if [[ "$TAG_LATEST" == true && "$tag" != "latest" ]]; then
            docker tag "$image_tag" "$FULL_IMAGE_NAME:latest"
            log_info "已标记为latest: $FULL_IMAGE_NAME:latest"
        fi
        
        # 如果需要自动推送
        if [[ "$AUTO_PUSH" == true ]]; then
            push_image "$tag"
        fi
    else
        log_error "镜像构建失败"
        exit 1
    fi
}

# 测试镜像
test_image() {
    local tag="$1"
    local image_tag="$FULL_IMAGE_NAME:$tag"
    
    log_info "测试Docker镜像: $image_tag"
    
    # 检查镜像是否存在
    if ! docker image inspect "$image_tag" >/dev/null 2>&1; then
        log_error "镜像不存在: $image_tag"
        exit 1
    fi
    
    # 基本功能测试
    log_info "运行基本功能测试..."
    docker run --rm "$image_tag" php -v
    docker run --rm "$image_tag" php -l /app/bin/pvm-mirror
    
    # 环境变量测试
    log_info "测试环境变量..."
    docker run --rm -e PVM_MIRROR_ENV=testing "$image_tag" env | grep PVM_MIRROR_ | wc -l
    
    # 启动测试
    log_info "测试服务启动..."
    local container_name="pvm-mirror-test-$$"
    
    docker run -d --name "$container_name" \
        -p 34404:34403 \
        -e PVM_MIRROR_ENV=testing \
        -e PVM_MIRROR_DEBUG=true \
        "$image_tag"
    
    # 等待服务启动
    sleep 10
    
    # 健康检查
    if curl -f http://localhost:34404/ >/dev/null 2>&1; then
        log_info "健康检查通过"
    else
        log_error "健康检查失败"
        docker logs "$container_name"
        docker stop "$container_name" >/dev/null 2>&1
        docker rm "$container_name" >/dev/null 2>&1
        exit 1
    fi
    
    # 清理
    docker stop "$container_name" >/dev/null 2>&1
    docker rm "$container_name" >/dev/null 2>&1
    
    log_info "镜像测试通过: $image_tag"
}

# 推送镜像
push_image() {
    local tag="$1"
    local image_tag="$FULL_IMAGE_NAME:$tag"
    
    log_info "推送Docker镜像: $image_tag"
    
    # 检查镜像是否存在
    if ! docker image inspect "$image_tag" >/dev/null 2>&1; then
        log_error "镜像不存在: $image_tag"
        exit 1
    fi
    
    # 推送镜像
    if docker push "$image_tag"; then
        log_info "镜像推送成功: $image_tag"
        
        # 如果需要推送latest标签
        if [[ "$TAG_LATEST" == true && "$tag" != "latest" ]]; then
            docker push "$FULL_IMAGE_NAME:latest"
            log_info "latest标签推送成功: $FULL_IMAGE_NAME:latest"
        fi
    else
        log_error "镜像推送失败"
        exit 1
    fi
}

# 运行容器
run_container() {
    local tag="$1"
    local image_tag="$FULL_IMAGE_NAME:$tag"
    local container_name="pvm-mirror-$tag"
    
    log_info "运行Docker容器: $container_name"
    
    # 停止并删除已存在的容器
    if docker ps -a --format "table {{.Names}}" | grep -q "^$container_name$"; then
        log_warn "容器已存在，正在停止并删除..."
        docker stop "$container_name" >/dev/null 2>&1 || true
        docker rm "$container_name" >/dev/null 2>&1 || true
    fi
    
    # 运行新容器
    docker run -d --name "$container_name" \
        -p 34403:34403 \
        -e PVM_MIRROR_ENV=development \
        -e PVM_MIRROR_DEBUG=true \
        -e PVM_MIRROR_LOG_LEVEL=debug \
        "$image_tag"
    
    log_info "容器启动成功: $container_name"
    log_info "访问地址: http://localhost:34403"
    log_info "查看日志: docker logs -f $container_name"
    log_info "停止容器: docker stop $container_name"
}

# 清理镜像和容器
clean_up() {
    log_info "清理PVM-Mirror相关的Docker资源..."
    
    # 停止并删除容器
    local containers=$(docker ps -a --filter "name=pvm-mirror" --format "{{.Names}}")
    if [[ -n "$containers" ]]; then
        log_info "停止并删除容器..."
        echo "$containers" | xargs -r docker stop
        echo "$containers" | xargs -r docker rm
    fi
    
    # 删除镜像
    local images=$(docker images --filter "reference=$FULL_IMAGE_NAME" --format "{{.Repository}}:{{.Tag}}")
    if [[ -n "$images" ]]; then
        log_info "删除镜像..."
        echo "$images" | xargs -r docker rmi
    fi
    
    # 清理构建缓存
    docker builder prune -f
    
    log_info "清理完成"
}

# 登录容器注册表
login_registry() {
    log_info "登录到容器注册表: $REGISTRY"
    
    if [[ -n "$DOCKER_USERNAME" && -n "$DOCKER_PASSWORD" ]]; then
        echo "$DOCKER_PASSWORD" | docker login "$REGISTRY" -u "$DOCKER_USERNAME" --password-stdin
    else
        docker login "$REGISTRY"
    fi
    
    log_info "登录成功"
}

# 显示镜像信息
show_info() {
    log_info "PVM-Mirror Docker镜像信息"
    echo
    echo "注册表: $REGISTRY"
    echo "仓库: $REPOSITORY"
    echo "镜像名: $IMAGE_NAME"
    echo "完整名称: $FULL_IMAGE_NAME"
    echo
    
    # 显示本地镜像
    log_info "本地镜像:"
    docker images --filter "reference=$FULL_IMAGE_NAME" --format "table {{.Repository}}\t{{.Tag}}\t{{.Size}}\t{{.CreatedAt}}"
    
    # 显示运行中的容器
    log_info "运行中的容器:"
    docker ps --filter "ancestor=$FULL_IMAGE_NAME" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
}

# 主函数
main() {
    parse_args "$@"
    
    if [[ -z "$COMMAND" ]]; then
        show_help
        exit 1
    fi
    
    check_docker
    
    case "$COMMAND" in
        build)
            build_image "$TAG"
            ;;
        test)
            test_image "$TAG"
            ;;
        push)
            push_image "$TAG"
            ;;
        run)
            run_container "$TAG"
            ;;
        clean)
            clean_up
            ;;
        login)
            login_registry
            ;;
        info)
            show_info
            ;;
        help)
            show_help
            ;;
        *)
            log_error "未知命令: $COMMAND"
            show_help
            exit 1
            ;;
    esac
}

# 执行主函数
main "$@"
