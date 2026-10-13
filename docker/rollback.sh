#!/usr/bin/env bash
# 回滚脚本: bash docker/rollback.sh [commit]
#   不带参数时,回滚到上一次部署前由 deploy 记录的提交(/home/iranftoy/last_release)
#   带参数时,回滚到指定的提交
set -euo pipefail

APP_DIR="/home/iranftoy/f1music-rebuild"
MARKER="/home/iranftoy/last_release"

cd "$APP_DIR"

TARGET="${1:-}"
if [ -z "$TARGET" ]; then
    if [ -f "$MARKER" ]; then
        TARGET="$(cat "$MARKER")"
    else
        echo "未指定提交,且找不到回滚标记 $MARKER" >&2
        exit 1
    fi
fi

git rev-parse --verify "${TARGET}^{commit}" >/dev/null
echo "== 当前版本: $(git rev-parse --short HEAD) =="
echo "== 回滚目标: $TARGET =="

git reset --hard "$TARGET"
cp -f docker-compose.prod.yml docker-compose.yml

docker compose up -d --build
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan migrate --force

echo "== 已回滚到: $(git rev-parse --short HEAD) =="
