#!/usr/bin/env bash
# Полный раунд проверок на локальном стенде (docker/setup.sh уже выполнен). Любой ненулевой код — раунд красный.
# Запуск из корня репозитория: docker/round.sh  (DC — команда compose, по умолчанию «docker compose»).
# Playwright запускается на хосте (npx) против http://127.0.0.1:${HTTP_PORT:-8096}.
set -euo pipefail
cd "$(dirname "$0")/.."
DC=${DC:-docker compose}
run() { printf '\n==> %s\n' "$*"; "$@"; }
php() { $DC exec -T -u "$(id -u):$(id -g)" php "$@"; }
node() { $DC run --rm -T node "$@"; }

echo "Коммит: $(git rev-parse --short HEAD)$(git diff --quiet HEAD -- . ':!docs/screenshots' || echo ' (+ незакоммиченные правки)')"
run docker/check-repo.sh
run php composer cs:check
run php composer stan
run node npm run types:check
run node npm run lint:check
run node npm run format:check
run node npm run build
run env DC="$DC" docker/test-db.sh
run php composer test
printf '\n==> cli/fixtures.php\n'
out=$(php php local/modules/dsefremov.shopclassic/cli/fixtures.php 2>&1)
printf '%s\n' "$out"
grep -q 'Демо-данные загружены' <<<"$out"  # ошибки ядра в CLI выходят с кодом 0
run npx playwright test --project=e2e
echo
echo "РАУНД ЗЕЛЁНЫЙ"
