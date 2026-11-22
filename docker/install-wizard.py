#!/usr/bin/env python3
"""Проходит мастер установки 1С-Битрикс по HTTP (без браузера), как это делает форма мастера.

Шаги: приветствие → лицензионное соглашение → регистрация (пропускается) → проверка системы →
параметры БД → установка модулей (AJAX-шаги, повторяются до __finish) → обновления (__finish) →
создание администратора. Шаг «Выбор решения» не проходится: демо-решение не нужно, сайт s1 уже
создан главным модулем, публичные страницы ставит модуль dsefremov.shopclassic.

Параметры — переменные окружения (значения по умолчанию — только для локального Docker):
HTTP_PORT, DB_HOST, DB_NAME, DB_USER, DB_PASSWORD (по умолчанию — из var/secrets/db-password), BX_ADMIN_LOGIN, BX_ADMIN_PASSWORD, BX_ADMIN_EMAIL.
Пароль системного администратора по умолчанию не задан: если BX_ADMIN_PASSWORD пуст, генерируется
случайный и сохраняется в var/bx-admin-password (права 600, каталог var/ не в git); в вывод не печатается.
"""
import html, http.cookiejar, os, re, secrets, sys, urllib.parse, urllib.request

BASE = 'http://127.0.0.1:' + os.environ.get('HTTP_PORT', '8096')
jar = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))


def request(data=None):
    body = urllib.parse.urlencode(data).encode() if data is not None else None
    return opener.open(BASE + '/', body, timeout=900).read().decode('utf-8', 'replace')


def form_fields(page):
    result = {}
    for match in re.finditer(r'<input[^>]*>', page):
        tag = match.group(0)
        name = re.search(r'name="([^"]*)"', tag)
        value = re.search(r'value="([^"]*)"', tag)
        kind = re.search(r'type="([^"]*)"', tag)
        kind = kind.group(1) if kind else 'text'
        if not name or kind == 'submit' or (kind in ('checkbox', 'radio') and 'checked' not in tag):
            continue
        result[name.group(1)] = html.unescape(value.group(1)) if value else ''
    return result


def step_id(page):
    m = re.search(r'name="CurrentStepID" value="([^"]*)"', page)
    return m.group(1) if m else '?'


def text(page):
    page = re.sub(r'<script.*?</script>|<style.*?</style>', '', page, flags=re.S)
    return re.sub(r'\s+', ' ', html.unescape(re.sub(r'<[^>]+>', ' ', page)))[-1500:]


def next_step(page, extra=None):
    data = form_fields(page)
    data.update(extra or {})
    data['StepNext'] = 'Далее'
    return request(data)


def ajax_steps(page):
    """AJAX-шаги мастера: ответ содержит JS с ajaxForm.Post({...}); повторяем, пока не придёт __finish."""
    data = form_fields(page)
    for _ in range(1000):
        answer = request(data)
        m = re.search(r'\[response\](.*?)\[/response\]', answer, re.S | re.I)
        if not m:
            sys.exit('Мастер вернул ошибку на AJAX-шаге: ' + text(answer))
        post = re.search(r'ajaxForm\.Post\(\{(.*?)\}', m.group(1), re.S)
        if not post:
            sys.exit('Неожиданный ответ AJAX-шага: ' + m.group(1)[:1000])
        for key, value in re.findall(r"'(\w+)'\s*:\s*'([^']*)'", post.group(1)):
            data['__wiz_' + key] = value
        print('  ', data.get('__wiz_nextStep'), data.get('__wiz_nextStepStage'), flush=True)
        if data.get('__wiz_nextStep') == '__finish':
            return request(data)
    sys.exit('AJAX-шаги не завершились')


env = os.environ.get
ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..')


def db_password():
    """Пароль пользователя БД: DB_PASSWORD или файл var/secrets/db-password (docker/secrets.sh)."""
    value = env('DB_PASSWORD', '')
    if value:
        return value
    with open(os.path.join(ROOT, 'var', 'secrets', 'db-password')) as f:
        return f.read().strip()


page = request()
expect = [
    ('welcome', {}),
    ('agreement', {'__wiz_agree_license': 'Y'}),
    ('check_license_key', {'__wiz_lic_key_variant': 'N'}),
    ('requirements', {}),
    ('create_database', {
        '__wiz_host': env('DB_HOST', '127.0.0.1:' + env('DB_PORT', '33076')),
        '__wiz_create_user': 'N', '__wiz_user': env('DB_USER', 'shop'),
        '__wiz_password': db_password(),
        '__wiz_create_database': 'N', '__wiz_database': env('DB_NAME', 'bitrix_shop_classic'),
        '__wiz_file_access_perms': '0664', '__wiz_folder_access_perms': '0775',
    }),
]
for step, extra in expect:
    if step_id(page) != step:
        sys.exit(f'Ожидался шаг {step}, получен {step_id(page)}: ' + text(page))
    print('шаг', step, flush=True)
    page = next_step(page, extra)

for step in ('create_modules', 'update_modules'):
    if step_id(page) != step:
        sys.exit(f'Ожидался шаг {step}, получен {step_id(page)}: ' + text(page))
    print('шаг', step, flush=True)
    page = ajax_steps(page)

if step_id(page) != 'create_admin':
    sys.exit('Ожидался шаг create_admin, получен ' + step_id(page) + ': ' + text(page))
print('шаг create_admin', flush=True)
password = env('BX_ADMIN_PASSWORD', '')
if not password:
    password = secrets.token_urlsafe(18)
    path = os.path.join(ROOT, 'var', 'bx-admin-password')
    os.makedirs(os.path.dirname(path), exist_ok=True)
    fd = os.open(path, os.O_WRONLY | os.O_CREAT | os.O_TRUNC, 0o600)
    with os.fdopen(fd, 'w') as f:
        f.write(password + '\n')
    print('Пароль администратора ядра сохранён в var/bx-admin-password', flush=True)
page = next_step(page, {
    '__wiz_login': env('BX_ADMIN_LOGIN', 'bxadmin'), '__wiz_admin_password': password,
    '__wiz_admin_password_confirm': password, '__wiz_email': env('BX_ADMIN_EMAIL', 'bxadmin@bitrix-shop-classic.test'),
    '__wiz_user_name': 'Системный', '__wiz_user_surname': 'Администратор',
})
if step_id(page) != 'select_wizard':
    sys.exit('После создания администратора ожидался выбор решения, получен ' + step_id(page) + ': ' + text(page))
print('Ядро установлено. Шаг «Выбор решения» пропущен (демо-решение не нужно).')
