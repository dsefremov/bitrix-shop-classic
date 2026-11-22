<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Service;

/**
 * Данные формы оформления заказа (проверенные).
 */
final readonly class CheckoutData
{
    public function __construct(
        public string $customerName,
        public string $customerEmail,
        public string $customerPhone,
        public string $address,
        public ?string $comment,
    ) {
    }

    /**
     * @param array<array-key, mixed> $input
     *
     * @return self|array<string, string> данные или ошибки по полям
     */
    public static function fromInput(array $input): self|array
    {
        $v = new Validator($input);
        $v->required('customer_name')->max('customer_name', 255)
            ->required('customer_email')->email('customer_email')->max('customer_email', 255)
            ->required('customer_phone')->max('customer_phone', 30)
            ->regex('customer_phone', '/^[0-9+()\-\s]{6,30}$/u', 'Укажите телефон: цифры, +, скобки, дефис, от 6 символов.')
            ->required('address')->max('address', 1000)
            ->max('comment', 1000);
        if ($v->fails()) {
            return $v->errors();
        }
        $comment = $v->string('comment');

        return new self(
            $v->string('customer_name'),
            $v->string('customer_email'),
            $v->string('customer_phone'),
            $v->string('address'),
            '' !== $comment ? $comment : null,
        );
    }
}
