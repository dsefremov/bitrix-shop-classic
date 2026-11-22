<?php

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Dsefremov\ShopClassic\Component\ShopComponent;
use Dsefremov\ShopClassic\Security\Access;
use Dsefremov\ShopClassic\Service\BasketService;
use Dsefremov\ShopClassic\Service\CheckoutData;
use Dsefremov\ShopClassic\Service\CheckoutException;
use Dsefremov\ShopClassic\Service\CheckoutService;
use Dsefremov\ShopClassic\Service\Flash;
use Dsefremov\ShopClassic\Service\UserService;
use Dsefremov\ShopClassic\View\Url;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
Loader::includeModule('dsefremov.shopclassic');

/**
 * Оформление заказа — свой компонент вместо sale.order.ajax: только для вошедших, пустая корзина —
 * обратно в корзину. POST (sessid) создаёт Sale\Order через CheckoutService (одна транзакция,
 * блокировка и проверка остатка) и уводит на страницу заказа с оплатой (PRG).
 */
final class DsefremovSaleCheckoutComponent extends ShopComponent
{
    public const string FORM = 'checkout';

    public function executeComponent(): void
    {
        if (!$this->requireUser()) {
            return;
        }
        $basket = new BasketService();
        $request = Context::getCurrent()->getRequest();
        if ($request->isPost()) {
            $this->place($basket, $request->getPostList()->toArray());
        }
        $lines = $basket->lines();
        if ([] === $lines) {
            Flash::error('Корзина пуста.');
            $this->redirect('/cart/');
        }
        $form = Flash::pullForm(self::FORM);
        $user = new UserService()->find(Access::userId());
        $this->arResult = [
            'LINES' => $lines,
            'TOTAL' => BasketService::total($lines),
            'ERRORS' => $form['errors'],
            'VALUES' => $form['old'] + ['customer_name' => $user['name'] ?? '', 'customer_email' => $user['email'] ?? ''],
        ];
        $this->setTitle('Оформление заказа');
        $this->includeComponentTemplate();
    }

    /**
     * @param array<array-key, mixed> $post
     */
    private function place(BasketService $basket, array $post): void
    {
        if (!check_bitrix_sessid()) {
            CHTTP::SetStatus('400 Bad Request');
            Flash::form(self::FORM, ['_form' => 'Сессия устарела. Отправьте форму ещё раз.'], $post);
            $this->redirect('/checkout/');
        }
        $data = CheckoutData::fromInput($post);
        if (\is_array($data)) {
            Flash::form(self::FORM, $data, $post);
            $this->redirect('/checkout/');
        }
        try {
            $orderId = new CheckoutService()->place(Access::userId(), $data, $basket);
        } catch (CheckoutException $e) {
            Flash::form(self::FORM, ['_form' => $e->getMessage()], $post);
            $this->redirect('/checkout/');
        }
        Flash::success("Заказ №{$orderId} оформлен. Оплатите его.");
        $this->redirect(Url::order($orderId));
    }
}
