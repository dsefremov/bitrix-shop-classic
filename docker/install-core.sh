#!/usr/bin/env bash
# Скачивает и распаковывает ядро 1С-Битрикс «Управление сайтом» (редакция «Малый бизнес», пробная версия на 30 дней)
# официальным скриптом bitrixsetup.php. Скрипт кладётся в корень сайта и проходится по HTTP так же,
# как это делает браузер: action=LOAD (докачка по шагам) → action=UNPACK (распаковка по шагам).
# Ядро в git не попадает (/bitrix в .gitignore). Запуск: docker/install-core.sh (стек должен быть поднят).
set -euo pipefail
cd "$(dirname "$0")/.."
BASE="http://127.0.0.1:${HTTP_PORT:-8096}"
EDITION_URL="${BITRIX_EDITION:-small_business}"   # start | standard | small_business | business

if [ -f bitrix/modules/main/include.php ]; then
    echo "Ядро уже распаковано (bitrix/modules/main), пропускаю загрузку."
    exit 0
fi

curl -fsSL -o bitrixsetup.php https://www.1c-bitrix.ru/download/scripts/bitrixsetup.php
chmod 664 bitrixsetup.php

next="/bitrixsetup.php?action=LOAD&edition=0&url=${EDITION_URL}&lang=ru&start=Y"
for i in $(seq 1 2000); do
    page=$(curl -fsS "${BASE}${next}")
    step=$(printf '%s' "$page" | grep -o 'GoToPage("[^"]*")' | head -1 | sed -e 's/^GoToPage("//' -e 's/")$//' || true)
    if [ -z "$step" ]; then
        break
    fi
    next="/${step#/}"
    case "$next" in
        *UNPACK*) printf 'u' ;;
        *) printf '.' ;;
    esac
done
echo
if [ ! -f bitrix/modules/main/include.php ]; then
    echo "Не удалось распаковать ядро. Последний ответ bitrixsetup.php:" >&2
    printf '%s\n' "$page" | sed -e 's/<[^>]*>/ /g' | tr -s ' \n' | tail -c 2000 >&2
    exit 1
fi
rm -f bitrixsetup.php
echo "Ядро распаковано: $(grep -o '"SM_VERSION", *"[^"]*"' bitrix/modules/main/classes/general/version.php 2>/dev/null || true)"
