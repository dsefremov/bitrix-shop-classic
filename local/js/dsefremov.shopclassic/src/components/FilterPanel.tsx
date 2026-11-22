import { useState } from 'react';
import type { FormEvent } from 'react';
import { SlidersIcon } from '../lib/icons';
import { visitList } from '../lib/url';

type Field =
    | { name: string; label: string; type: 'number'; placeholder?: string }
    | { name: string; label: string; type: 'date' }
    | { name: string; label: string; type: 'checkbox' }
    | { name: string; label: string; type: 'select'; options: { value: string; label: string }[] };

type Props = {
    fields: Field[];
    values: Record<string, string | null>;
    title?: string;
    /** card — колонка в боковой панели каталога; row — строка над списком в ЛК и админке. */
    layout?: 'card' | 'row';
    applyTestId?: string;
    /** Служебные GET-параметры, которые отправляются вместе с фильтром (например set_filter=y для bitrix:catalog.smart.filter). */
    extra?: Record<string, string>;
};

const TRUTHY = ['1', 'true', 'on', 'yes'];

/**
 * Панель фильтров: значения применяются кнопкой «Применить» и попадают в GET-параметры;
 * «Сбросить» убирает только фильтры этой панели (поиск и сортировка сохраняются).
 */
export default function FilterPanel({
    fields,
    values,
    title,
    layout = 'row',
    applyTestId = 'apply-filters',
    extra = {},
}: Props) {
    const [state, setState] = useState<Record<string, string | boolean>>(() =>
        Object.fromEntries(
            fields.map((field) => [
                field.name,
                field.type === 'checkbox'
                    ? TRUTHY.includes(values[field.name] ?? '')
                    : (values[field.name] ?? ''),
            ]),
        ),
    );

    const hasActive = fields.some((field) => (values[field.name] ?? '') !== '');

    function apply(e: FormEvent) {
        e.preventDefault();
        visitList({ ...state, ...extra });
    }

    function reset() {
        visitList(
            Object.fromEntries(
                [...fields.map((field) => field.name), ...Object.keys(extra)].map((name) => [
                    name,
                    null,
                ]),
            ),
        );
    }

    function set(name: string, value: string | boolean) {
        setState((prev) => ({ ...prev, [name]: value }));
    }

    const isCard = layout === 'card';

    return (
        <form
            onSubmit={apply}
            className={isCard ? 'card space-y-3 p-4' : 'flex flex-wrap items-end gap-3'}
            aria-label={title ?? 'Фильтры'}
        >
            {title && (
                <div className="flex items-center gap-2 text-sm font-semibold">
                    <SlidersIcon />
                    {title}
                </div>
            )}
            <div className={isCard ? 'grid grid-cols-2 gap-2' : 'contents'}>
                {fields.map((field) => {
                    const id = `filter-${field.name}`;

                    if (field.type === 'checkbox') {
                        return (
                            <label
                                key={field.name}
                                htmlFor={id}
                                className={
                                    'flex h-9 items-center gap-2 text-sm ' +
                                    (isCard ? 'col-span-2' : '')
                                }
                            >
                                <input
                                    id={id}
                                    type="checkbox"
                                    className="size-4 rounded border-zinc-300 accent-zinc-900"
                                    checked={state[field.name] === true}
                                    onChange={(e) => set(field.name, e.target.checked)}
                                />
                                {field.label}
                            </label>
                        );
                    }

                    return (
                        <div
                            key={field.name}
                            className={isCard ? '' : 'w-[calc(50%-0.375rem)] sm:w-36'}
                        >
                            <label htmlFor={id} className="mb-1 block text-xs text-zinc-500">
                                {field.label}
                            </label>
                            {field.type === 'select' ? (
                                <select
                                    id={id}
                                    className="input"
                                    value={String(state[field.name] ?? '')}
                                    onChange={(e) => set(field.name, e.target.value)}
                                >
                                    {field.options.map((o) => (
                                        <option key={o.value} value={o.value}>
                                            {o.label}
                                        </option>
                                    ))}
                                </select>
                            ) : (
                                <input
                                    id={id}
                                    type={field.type}
                                    className="input"
                                    inputMode={field.type === 'number' ? 'decimal' : undefined}
                                    min={field.type === 'number' ? 0 : undefined}
                                    step={field.type === 'number' ? 'any' : undefined}
                                    placeholder={
                                        field.type === 'number' ? field.placeholder : undefined
                                    }
                                    value={String(state[field.name] ?? '')}
                                    onChange={(e) => set(field.name, e.target.value)}
                                />
                            )}
                        </div>
                    );
                })}
            </div>
            <div className={isCard ? 'grid grid-cols-2 gap-2' : 'flex gap-2'}>
                <button type="submit" className="btn btn-primary px-3" data-testid={applyTestId}>
                    Применить
                </button>
                <button
                    type="button"
                    className="btn btn-outline px-3"
                    onClick={reset}
                    disabled={!hasActive}
                    data-testid="reset-filters"
                >
                    Сбросить
                </button>
            </div>
        </form>
    );
}
