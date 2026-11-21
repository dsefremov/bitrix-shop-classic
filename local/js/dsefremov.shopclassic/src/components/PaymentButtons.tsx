import { useState } from 'react';

type Props = {
    action: string;
    csrf: string;
    paymentId: number;
    declined: boolean;
};

/**
 * Кнопки «Тестовой оплаты»: «Оплатить» и «Отказ». Форма уходит POST-запросом на страницу заказа,
 * ответ обрабатывает штатный PaySystem\Service::processRequest(). Повторное нажатие блокируется,
 * пока идёт отправка.
 */
export default function PaymentButtons({ action, csrf, paymentId, declined }: Props) {
    const [sending, setSending] = useState(false);

    return (
        <form
            method="post"
            action={action}
            className="flex gap-2"
            onSubmit={(e) => {
                if (sending) {
                    e.preventDefault();
                    return;
                }
                setSending(true);
            }}
        >
            <input type="hidden" name="sessid" value={csrf} />
            <input type="hidden" name="PAYMENT_ID" value={paymentId} />
            <button
                type="submit"
                name="STUB_RESULT"
                value="success"
                className="btn btn-primary flex-1"
                data-testid="pay-button"
                aria-disabled={sending}
            >
                {declined ? 'Оплатить ещё раз' : 'Оплатить'}
            </button>
            <button
                type="submit"
                name="STUB_RESULT"
                value="fail"
                className="btn btn-outline"
                data-testid="decline-button"
                aria-disabled={sending}
            >
                Отказ
            </button>
        </form>
    );
}
