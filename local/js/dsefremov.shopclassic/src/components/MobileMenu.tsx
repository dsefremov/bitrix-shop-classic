import { useEffect, useState } from 'react';
import { MenuIcon, XIcon } from '../lib/icons';

type Item = { title: string; href: string; active: boolean };

type Props = {
    items: Item[];
    user: { name: string; email: string } | null;
    links: { profile: string; login: string; register: string };
    logout: { action: string; csrf: string } | null;
};

/** Меню для узких экранов: выезжающая панель с навигацией и учётной записью. */
export default function MobileMenu({ items, user, links, logout }: Props) {
    const [open, setOpen] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }
        const onKey = (e: KeyboardEvent) => e.key === 'Escape' && setOpen(false);
        document.addEventListener('keydown', onKey);
        document.body.style.overflow = 'hidden';

        return () => {
            document.removeEventListener('keydown', onKey);
            document.body.style.overflow = '';
        };
    }, [open]);

    return (
        <>
            <button
                type="button"
                className="btn btn-ghost size-9 px-0"
                aria-label="Открыть меню"
                aria-expanded={open}
                onClick={() => setOpen(true)}
            >
                <MenuIcon className="size-5" />
            </button>
            {open && (
                <div
                    className="fixed inset-0 z-50"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Меню"
                >
                    <div className="absolute inset-0 bg-black/40" onClick={() => setOpen(false)} />
                    <div className="absolute inset-y-0 left-0 flex w-72 max-w-[85vw] flex-col gap-4 bg-white p-4 shadow-xl">
                        <div className="flex items-center justify-between">
                            <span className="font-semibold">Меню</span>
                            <button
                                type="button"
                                className="btn btn-ghost size-9 px-0"
                                aria-label="Закрыть меню"
                                onClick={() => setOpen(false)}
                            >
                                <XIcon className="size-5" />
                            </button>
                        </div>
                        <nav className="flex flex-col gap-1" aria-label="Мобильное меню">
                            {items.map((item) => (
                                <a
                                    key={item.href}
                                    href={item.href}
                                    className={
                                        'rounded-md px-3 py-2 text-sm ' +
                                        (item.active
                                            ? 'bg-zinc-100 font-medium'
                                            : 'hover:bg-zinc-50')
                                    }
                                >
                                    {item.title}
                                </a>
                            ))}
                        </nav>
                        <div className="mt-auto border-t pt-4">
                            {user ? (
                                <div className="space-y-2">
                                    <div className="px-3 text-sm">
                                        <div className="font-medium">{user.name}</div>
                                        <div className="truncate text-zinc-500">{user.email}</div>
                                    </div>
                                    <a
                                        href={links.profile}
                                        className="block rounded-md px-3 py-2 text-sm hover:bg-zinc-50"
                                    >
                                        Профиль
                                    </a>
                                    {logout && (
                                        <form method="post" action={logout.action}>
                                            <input
                                                type="hidden"
                                                name="sessid"
                                                value={logout.csrf}
                                            />
                                            <button
                                                type="submit"
                                                className="w-full rounded-md px-3 py-2 text-left text-sm hover:bg-zinc-50"
                                            >
                                                Выйти
                                            </button>
                                        </form>
                                    )}
                                </div>
                            ) : (
                                <div className="flex gap-2">
                                    <a href={links.login} className="btn btn-outline flex-1">
                                        Войти
                                    </a>
                                    <a href={links.register} className="btn btn-primary flex-1">
                                        Регистрация
                                    </a>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
