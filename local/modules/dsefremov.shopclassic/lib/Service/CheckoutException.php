<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Service;

/**
 * Заказ нельзя оформить (пустая корзина, нехватка остатка, товар снят с продажи).
 */
final class CheckoutException extends \RuntimeException
{
}
