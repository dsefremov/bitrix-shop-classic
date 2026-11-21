#!/usr/bin/env bash
# Тестовая база bitrix_shop_classic_test = копия основной (ядро + модули iblock/catalog/sale + модуль
# dsefremov.shopclassic со своими миграциями) без демо-данных магазина: tests/prepare-db.php чистит товары,
# заказы, корзины и тестовых пользователей. Запуск: docker/test-db.sh (из корня репозитория, стенд запущен).
set -euo pipefail
cd "$(dirname "$0")/.."
DC=${DC:-docker compose}
PORT=${DB_PORT:-33076}
# Порт передаётся в контейнер явно: сеть хоста, и без него mysql-клиент попал бы на чужой сервер.
# Пароль root читается внутри контейнера из compose secret и в командную строку не попадает.
mysql_root() { $DC exec -T -e DB_PORT="$PORT" mysql sh -c "export MYSQL_PWD=\"\$(cat /run/secrets/db_root_password)\"; $1"; }

mysql_root 'mysql -uroot -h127.0.0.1 -P"$DB_PORT" -e "DROP DATABASE IF EXISTS bitrix_shop_classic_test; CREATE DATABASE bitrix_shop_classic_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL PRIVILEGES ON bitrix_shop_classic_test.* TO \"shop\"@\"%\";"'
mysql_root 'set -e; mysqldump -uroot -h127.0.0.1 -P"$DB_PORT" --single-transaction --no-tablespaces bitrix_shop_classic > /tmp/bitrix_shop_classic_test.sql; mysql -uroot -h127.0.0.1 -P"$DB_PORT" bitrix_shop_classic_test < /tmp/bitrix_shop_classic_test.sql; rm -f /tmp/bitrix_shop_classic_test.sql'

# Ошибки ядра в CLI выводятся обработчиком исключений с кодом 0 — поэтому проверяется строка успеха.
out=$($DC exec -T php php tests/prepare-db.php 2>&1) || { printf '%s\n' "$out" >&2; exit 1; }
printf '%s\n' "$out"
grep -q 'Тестовая база bitrix_shop_classic_test готова.' <<<"$out" || { echo 'Тестовая база не подготовлена.' >&2; exit 1; }
