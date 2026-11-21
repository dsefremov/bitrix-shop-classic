<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Service;

use Dsefremov\ShopClassic\Config;

/**
 * Состояние заказа для публичной части: статус sale + отмена + отметка «Оплата отклонена».
 */
final readonly class OrderView
{
    public function __construct(
        public int $id,
        public string $statusId,
        public bool $canceled,
        public bool $paid,
        public bool $declined,
    ) {
    }

    public function label(): string
    {
        if ($this->canceled) {
            return 'Отменён';
        }

        return Config::STATUSES[$this->statusId] ?? $this->statusId;
    }

    /**
     * Тон бейджа (Html::statusBadge).
     */
    public function tone(): string
    {
        return match (true) {
            $this->canceled => 'muted',
            Config::STATUS_COMPLETED === $this->statusId => 'success',
            Config::STATUS_PAID === $this->statusId => 'info',
            default => 'warning',
        };
    }

    public function isPayable(): bool
    {
        return !$this->canceled && !$this->paid;
    }
}
