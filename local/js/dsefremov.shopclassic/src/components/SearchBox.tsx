import { useEffect, useRef, useState } from 'react';
import { SearchIcon, XIcon } from '../lib/icons';
import { visitList } from '../lib/url';

type Props = {
    value: string | null;
    placeholder: string;
    label: string;
    testId: string;
    name?: string;
};

const FOCUS_KEY = 'shop:focus-search';
const DELAY_MS = 300;

/**
 * Поиск без кнопки: через 300 мс после ввода адрес обновляется (?q=...),
 * страница перерисовывается сервером, фокус и курсор возвращаются в поле.
 */
export default function SearchBox({ value, placeholder, label, testId, name = 'q' }: Props) {
    const [term, setTerm] = useState(value ?? '');
    const input = useRef<HTMLInputElement>(null);
    const timer = useRef<number | undefined>(undefined);

    useEffect(() => {
        if (sessionStorage.getItem(FOCUS_KEY) === testId && input.current) {
            sessionStorage.removeItem(FOCUS_KEY);
            const el = input.current;
            el.focus();
            el.setSelectionRange(el.value.length, el.value.length);
        }

        return () => window.clearTimeout(timer.current);
    }, [testId]);

    function go(next: string) {
        window.clearTimeout(timer.current);
        if (next.trim() === (value ?? '')) {
            return;
        }
        sessionStorage.setItem(FOCUS_KEY, testId);
        visitList({ [name]: next });
    }

    function onChange(next: string) {
        setTerm(next);
        window.clearTimeout(timer.current);
        timer.current = window.setTimeout(() => go(next), DELAY_MS);
    }

    return (
        <div className="relative w-full">
            <SearchIcon className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-zinc-400" />
            <input
                ref={input}
                type="search"
                className="input pr-9 pl-9"
                aria-label={label}
                placeholder={placeholder}
                maxLength={100}
                value={term}
                data-testid={testId}
                onChange={(e) => onChange(e.target.value)}
                onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        go(term);
                    }
                }}
            />
            {term !== '' && (
                <button
                    type="button"
                    className="absolute top-1/2 right-2 -translate-y-1/2 rounded p-1 text-zinc-400 hover:text-zinc-700"
                    aria-label="Очистить поиск"
                    onClick={() => {
                        setTerm('');
                        go('');
                    }}
                >
                    <XIcon />
                </button>
            )}
        </div>
    );
}
