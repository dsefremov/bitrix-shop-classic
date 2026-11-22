<?php

use Bitrix\Main\Loader;
use Dsefremov\ShopClassic\Component\ShopComponent;
use Dsefremov\ShopClassic\Security\Access;
use Dsefremov\ShopClassic\Service\OrderListService;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
Loader::includeModule('dsefremov.shopclassic');

/**
 * «Мои заказы» (заказы sale текущего покупателя) — только для вошедших.
 */
final class DsefremovSaleOrderListComponent extends ShopComponent
{
    public function executeComponent(): void
    {
        if (!$this->requireUser()) {
            return;
        }
        $service = new OrderListService();
        $this->arResult = $service->find(Access::userId(), $this->query()) + ['HAS_ANY' => $service->hasAny(Access::userId())];
        $this->setTitle('Мои заказы');
        $this->includeComponentTemplate();
    }
}
