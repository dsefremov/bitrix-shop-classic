import { visitList } from '../lib/url';

type Option = { value: string; label: string };

type Props = {
    options: Option[];
    sort: string;
    dir: string;
    defaultSort: string;
    defaultDir: string;
    label: string;
    testId: string;
};

/** Сортировка списком «поле:направление»; значения — только из белого списка сервера. */
export default function SortSelect({
    options,
    sort,
    dir,
    defaultSort,
    defaultDir,
    label,
    testId,
}: Props) {
    const current = `${sort}:${dir}`;

    return (
        <select
            className="input pr-8"
            aria-label={label}
            data-testid={testId}
            value={current}
            onChange={(e) => {
                const [nextSort, nextDir] = e.target.value.split(':');
                visitList({ sort: nextSort, dir: nextDir }, { sort: defaultSort, dir: defaultDir });
            }}
        >
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}
