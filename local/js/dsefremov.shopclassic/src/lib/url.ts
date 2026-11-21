/** Значение GET-параметра: пустое, null и false удаляют параметр, true -> '1'. */
export type ParamValue = string | boolean | null | undefined;

export type SortDefaults = { sort: string; dir: string };

/**
 * Новый адрес текущей страницы с изменёнными GET-параметрами.
 * Номер страницы (`page` и PAGEN_N постраничной навигации Битрикса) сбрасывается:
 * любая смена фильтра или сортировки ведёт на первую страницу.
 * Пара sort/dir по умолчанию не пишется, чтобы адрес оставался коротким.
 */
export function buildListUrl(
    updates: Record<string, ParamValue>,
    defaults?: SortDefaults,
    href: string = window.location.href,
): string {
    const url = new URL(href);
    const params = url.searchParams;

    for (const [key, value] of Object.entries(updates)) {
        const str = value === true ? '1' : typeof value === 'string' ? value.trim() : '';

        if (str === '') {
            params.delete(key);
        } else {
            params.set(key, str);
        }
    }

    params.delete('page');
    for (const key of [...params.keys()]) {
        if (/^PAGEN_\d+$/.test(key)) {
            params.delete(key);
        }
    }

    if (defaults && params.get('sort') === defaults.sort && params.get('dir') === defaults.dir) {
        params.delete('sort');
        params.delete('dir');
    }

    const search = params.toString();

    return url.pathname + (search ? `?${search}` : '');
}

/** Переход по списку: каждое изменение — отдельная запись в истории, «Назад» работает. */
export function visitList(updates: Record<string, ParamValue>, defaults?: SortDefaults): void {
    window.location.assign(buildListUrl(updates, defaults));
}
