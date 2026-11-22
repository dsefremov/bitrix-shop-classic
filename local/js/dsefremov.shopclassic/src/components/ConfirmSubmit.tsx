type Props = {
    action: string;
    csrf: string;
    label: string;
    confirm: string;
    ariaLabel?: string;
    variant?: 'danger' | 'ghost';
};

/** Кнопка-действие (POST + CSRF) с подтверждением, например «Удалить». */
export default function ConfirmSubmit({
    action,
    csrf,
    label,
    confirm,
    ariaLabel,
    variant = 'ghost',
}: Props) {
    return (
        <form
            method="post"
            action={action}
            onSubmit={(e) => {
                if (!window.confirm(confirm)) {
                    e.preventDefault();
                }
            }}
        >
            <input type="hidden" name="sessid" value={csrf} />
            <button
                type="submit"
                aria-label={ariaLabel}
                className={
                    'btn btn-sm ' +
                    (variant === 'danger' ? 'btn-danger' : 'text-red-600 hover:bg-red-50')
                }
            >
                {label}
            </button>
        </form>
    );
}
