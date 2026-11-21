#!/usr/bin/env bash
# Полная локальная установка с нуля: пароли в var/ → стек Docker → зависимости → ядро 1С-Битрикс
# «Малый бизнес» (пробная версия) через bitrixsetup.php → мастер установки → модуль dsefremov.shopclassic
# (инфоблок, торговый каталог, sale) → демо-данные → тестовая база.
# Запуск из корня репозитория: docker/setup.sh  (повторный запуск безопасен: готовые шаги пропускаются).
# Порты — переменные HTTP_PORT, TEST_HTTP_PORT, DB_PORT, FPM_PORT. Пароли генерируются в var/ (вне git)
# и никуда не печатаются. DC — команда compose (по умолчанию «docker compose», например DC="sudo docker compose").
set -euo pipefail
cd "$(dirname "$0")/.."
DC=${DC:-docker compose}
export UID GID=${GID:-$(id -g)}
step() { printf '\n==> %s\n' "$*"; }
# Ошибки ядра в CLI выводятся обработчиком исключений с кодом 0 — проверяем строку успеха.
expect() {
    local pattern=$1 out
    shift
    out=$("$@" 2>&1) || { printf '%s\n' "$out" >&2; exit 1; }
    printf '%s\n' "$out"
    grep -Eq "$pattern" <<<"$out" || { echo "Шаг не завершился: $*" >&2; exit 1; }
}

step "Локальные пароли (var/secrets)"
docker/secrets.sh

step "Сборка и запуск контейнеров"
$DC build php
$DC up -d --wait mysql
$DC up -d php nginx

step "Зависимости PHP и фронтенда, сборка Vite"
$DC exec -T php composer install --no-interaction --no-progress
$DC run --rm node npm ci --no-audit --no-fund
$DC run --rm node npm run build

step "Ядро 1С-Битрикс (bitrixsetup.php)"
docker/install-core.sh

if [ ! -f bitrix/.settings.php ]; then
    step "Мастер установки ядра"
    python3 docker/install-wizard.py
else
    echo "Ядро уже установлено (bitrix/.settings.php), мастер пропущен."
fi

step "Подключение к БД из окружения (bitrix/.settings_extra.php)"
cp docker/bitrix/settings_extra.php bitrix/.settings_extra.php

step "Модуль dsefremov.shopclassic и демо-данные"
expect 'Модуль dsefremov.shopclassic (установлен|обновлён)' $DC exec -T php php local/modules/dsefremov.shopclassic/cli/install.php
expect 'Демо-данные загружены' $DC exec -T php php local/modules/dsefremov.shopclassic/cli/fixtures.php

step "Тестовая база bitrix_shop_classic_test"
DC="$DC" docker/test-db.sh

echo
echo "Готово: http://127.0.0.1:${HTTP_PORT:-8096} (тестовый сайт для PHPUnit: http://127.0.0.1:${TEST_HTTP_PORT:-8097})"
echo "Учётные данные: var/bx-admin-password (администратор ядра), var/demo-users.json (менеджер и покупатели)."
