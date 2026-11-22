import { useRef, useState } from 'react';
import { MinusIcon, PlusIcon } from '../lib/icons';

type Props = {
    action: string;
    csrf: string;
    quantity: number;
    stock: number;
    productName: string;
};

/**
 * Количество в строке корзины: «−» / «+» сразу отправляют форму, верхняя граница — остаток.
 * 0 убирает товар из корзины.
 */
export default function CartQuantity({ action, csrf, quantity, stock, productName }: Props) {
    const form = useRef<HTMLFormElement>(null);
    const hidden = useRef<HTMLInputElement>(null);
    const [value, setValue] = useState(quantity);

    function submit(next: number) {
        setValue(next);
        if (hidden.current && form.current) {
            hidden.current.value = String(next);
            form.current.requestSubmit();
        }
    }

    return (
        <form ref={form} method="post" action={action} className="flex items-center">
            <input type="hidden" name="sessid" value={csrf} />
            <input ref={hidden} type="hidden" name="quantity" defaultValue={quantity} />
            <div className="flex items-center rounded-md border bg-white">
                <button
                    type="button"
                    className="flex size-8 items-center justify-center text-zinc-600 hover:bg-zinc-100"
                    aria-label={`Уменьшить количество: ${productName}`}
                    onClick={() => submit(value - 1)}
                >
                    <MinusIcon className="size-3.5" />
                </button>
                <span className="w-9 text-center text-sm tabular-nums" data-testid="cart-qty">
                    {value}
                </span>
                <button
                    type="button"
                    className="flex size-8 items-center justify-center text-zinc-600 hover:bg-zinc-100 disabled:opacity-40"
                    aria-label={`Увеличить количество: ${productName}`}
                    disabled={value >= stock}
                    title={value >= stock ? `В наличии только ${stock} шт.` : undefined}
                    onClick={() => submit(value + 1)}
                >
                    <PlusIcon className="size-3.5" />
                </button>
            </div>
        </form>
    );
}
