#!/usr/bin/env bash
# Проверки содержимого git (локально и в CI): ядра 1С-Битрикс и файлов с доступами в репозитории нет,
# в отслеживаемых файлах нет литералов паролей/токенов. Проверяются только файлы из `git ls-files`.
# Запуск из корня репозитория: docker/check-repo.sh   (код выхода ≠ 0 — проверка не прошла).
set -euo pipefail
cd "$(dirname "$0")/.."
fail=0
tracked() { git ls-files -z | tr '\0' '\n'; }

# 1. Ядро, загрузки, установщик, дампы и файлы с доступами не отслеживаются.
core=$(tracked | grep -E '^(bitrix|upload|var|dumps|vendor|node_modules)/|^bitrixsetup\.php$|(^|/)\.settings(_extra)?\.php$|(^|/)dbconn\.php$|\.sql(\.gz)?$|(^|/)\.env(\..*)?$' \
    | grep -vxE 'local/modules/[^/]+/\.settings\.php|docker/mysql/init\.sql' || true)
if [ -n "$core" ]; then
    echo "В git попали файлы ядра/доступов:"; echo "$core"; fail=1
fi
if [ -e bitrix ] && [ -n "${CI:-}" ]; then
    echo "В CI-клоне не должно быть каталога bitrix/"; fail=1
fi

# 2. Литералы паролей, секретов и токенов в отслеживаемых файлах (значение — строка, а не переменная).
pattern="(password|passwd|pwd|secret|token)['\"]?[[:space:]]*(=>|=|:)[[:space:]]*['\"][^'\"\$[:space:]]{3,}['\"]"
hits=$(tracked | grep -vE '(^|/)(package-lock\.json|composer\.lock)$|\.png$' | tr '\n' '\0' \
    | xargs -0 grep -nIiE "$pattern" -- 2>/dev/null || true)
if [ -n "$hits" ]; then
    echo "Похоже на пароль/секрет в коде:"; echo "$hits"; fail=1
fi

if [ "$fail" -ne 0 ]; then
    exit 1
fi
echo "check-repo: ядра и паролей в git нет ($(tracked | wc -l) файлов проверено)."
