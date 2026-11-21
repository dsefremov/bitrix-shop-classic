<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Service;

/**
 * Оплату нельзя провести (чужой/отменённый/оплаченный заказ). code 404 — заказ не найден.
 */
final class PaymentException extends \RuntimeException
{
}
