#!/usr/bin/env bash
# Генерирует локальные пароли в var/ (каталог вне git). Существующие файлы не перезаписываются.
#   var/secrets/db-root-password, var/secrets/db-password — MySQL (compose secrets, *_PASSWORD_FILE);
#   var/bx-admin-password — системный администратор ядра (создаёт docker/install-wizard.py);
#   var/demo-users.json — демо-учётки (создаёт cli/fixtures.php).
# Пароли никуда не печатаются.
set -euo pipefail
cd "$(dirname "$0")/.."
umask 077
mkdir -p var/secrets
chmod 700 var var/secrets
gen() { python3 -c 'import secrets; print(secrets.token_urlsafe(24))'; }
for name in db-root-password db-password; do
    if [ ! -s "var/secrets/$name" ]; then
        gen > "var/secrets/$name"
        echo "Создан var/secrets/$name"
    fi
    # MySQL в контейнере читает файл от своего пользователя (bind mount secrets): нужен доступ на чтение.
    chmod 644 "var/secrets/$name"
done
