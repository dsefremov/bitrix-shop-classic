import { useState } from 'react';
import { CartIcon, MinusIcon, PlusIcon } from '../lib/icons';

type Props = {
    action: string;
    csrf: string;
    productId: number;
    stock: number;
    inCart: number;
    /** compact — кнопка в карточке каталога, без выбора количества. */
    compact?: boolean;
    productName: string;
};

/**
 * «В корзину»: обычная POST-форма (CSRF), количество ограничено остатком минус уже лежащее в корзине.
 */
export default function AddToCart({
    action,
    csrf,
    productId,
    stock,
    inCart,
    compact,
    productName,
}: Props) {
    // Сколько ещё можно добавить; сервер всё равно ограничит количество остатком.
    const available = Math.max(1, stock - inCart);
    const [quantity, setQuantity] = useState('1');
    const disabled = stock < 1;
    const current = parseInt(quantity, 10) || 1;
    const clamp = (n: number) => String(Math.min(Math.max(1, n), available));

    if (compact) {
        return (
            <form method="post" action={action} className="w-full sm:w-auto">
                <input type="hidden" name="sessid" value={csrf} />
                <input type="hidden" name="product_id" value={productId} />
                <input type="hidden" name="quantity" value="1" />
                <button
                    type="submit"
                    className="btn btn-primary btn-sm w-full sm:w-auto"
                    disabled={disabled}
                    aria-label={`В корзину: ${productName}`}
                >
                    <CartIcon className="size-3.5" />В корзину
                </button>
            </form>
        );
    }

    return (
        <form method="post" action={action} className="flex flex-wrap items-end gap-3">
            <input type="hidden" name="sessid" value={csrf} />
            <input type="hidden" name="product_id" value={productId} />
            <div>
                <label htmlFor="add-quantity" className="mb-1 block text-xs text-zinc-500">
                    Количество
                </label>
                <div className="flex items-center rounded-md border bg-white">
                    <button
                        type="button"
                        className="flex size-9 items-center justify-center text-zinc-600 hover:bg-zinc-100 disabled:opacity-40"
                        aria-label="Меньше"
                        disabled={disabled || current <= 1}
                        onClick={() => setQuantity(clamp(current - 1))}
                    >
                        <MinusIcon />
                    </button>
                    <input
                        id="add-quantity"
                        name="quantity"
                        type="number"
                        inputMode="numeric"
                        min={1}
                        max={available}
                        className="h-9 w-14 border-x text-center text-sm [appearance:textfield] focus:outline-none [&::-webkit-inner-spin-button]:appearance-none"
                        value={quantity}
                        disabled={disabled}
                        onChange={(e) => setQuantity(e.target.value)}
                        onBlur={() => setQuantity(clamp(current))}
                    />
                    <button
                        type="button"
                        className="flex size-9 items-center justify-center text-zinc-600 hover:bg-zinc-100 disabled:opacity-40"
                        aria-label="Больше"
                        disabled={disabled || current >= available}
                        onClick={() => setQuantity(clamp(current + 1))}
                    >
                        <PlusIcon />
                    </button>
                </div>
            </div>
            <button
                type="submit"
                className="btn btn-primary h-9"
                disabled={disabled}
                data-testid="add-to-cart"
            >
                <CartIcon />
                {disabled ? 'Нет в наличии' : 'В корзину'}
            </button>
            {inCart > 0 && (
                <p className="w-full text-sm text-zinc-500" data-testid="in-cart">
                    В корзине: {inCart} шт.
                </p>
            )}
        </form>
    );
}
