#!/bin/bash

# Docker 执行包装脚本
# 解决容器内执行命令导致的文件权限问题

CONTAINER_NAME="pvm-mirror-dev"
HOST_USER=$(whoami)

# 执行命令
echo "执行命令: docker exec $CONTAINER_NAME $@"
docker exec "$CONTAINER_NAME" "$@"
EXEC_RESULT=$?

# 修复权限问题
echo "正在修复文件权限..."
find . -type f -user root 2>/dev/null | while read file; do
    if [ -f "$file" ]; then
        echo "修复权限: $file"
        sudo chown "$HOST_USER:$HOST_USER" "$file" 2>/dev/null
    fi
done

find . -type d -user root 2>/dev/null | while read dir; do
    if [ -d "$dir" ]; then
        echo "修复目录权限: $dir"
        sudo chown "$HOST_USER:$HOST_USER" "$dir" 2>/dev/null
    fi
done

echo "权限修复完成"
exit $EXEC_RESULT
