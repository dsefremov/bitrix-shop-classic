<?php

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Sale\PaySystem\BaseServiceHandler;
use Bitrix\Sale\PaySystem\Manager as PaySystemManager;
use Bitrix\Sale\PaySystem\Service;
use Dsefremov\ShopClassic\Component\ShopComponent;
use Dsefremov\ShopClassic\Security\Access;
use Dsefremov\ShopClassic\Service\Flash;
use Dsefremov\ShopClassic\Service\OrderService;
use Dsefremov\ShopClassic\Service\PaymentException;
use Dsefremov\ShopClassic\Service\PaymentService;
use Dsefremov\ShopClassic\View\Url;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
Loader::includeModule('dsefremov.shopclassic');

/**
 * Страница оплаты заказа (параметр ORDER_ID). GET — блок оплаты: штатный PaySystem\Service::initiatePay()
 * выводит шаблон платёжной системы (кнопки «Оплатить» / «Отказ»). POST (sessid) — ответ «Тестовой оплаты»
 * через PaymentService (штатный processRequest), затем редирект на эту же страницу с сообщением.
 */
final class DsefremovSaleOrderPaymentComponent extends ShopComponent
{
    public function executeComponent(): void
    {
        if (!$this->requireUser()) {
            return;
        }
        $orderId = is_numeric($this->arParams['ORDER_ID'] ?? null) ? (int) $this->arParams['ORDER_ID'] : 0;
        $request = Context::getCurrent()->getRequest();
        if ($request->isPost() && null !== $request->getPost('STUB_RESULT')) {
            $this->pay($orderId, 'success' === $request->getPost('STUB_RESULT'));
        }

        $orders = new OrderService();
        $order = $orders->loadForUser($orderId, Access::userId());
        if (null === $order) {
            return;
        }
        $payment = $orders->stubPayment($order);
        $declined = $orders->isDeclined($order);
        $form = '';
        if (null !== $payment && !$order->isPaid() && !$order->isCanceled()) {
            $service = PaySystemManager::getObjectById((int) $payment->getPaymentSystemId());
            if ($service instanceof Service) {
                $service->setTemplateParams([
                    'ACTION' => Url::order($orderId),
                    'PAYMENT_ID' => (int) $payment->getId(),
                    'DECLINED' => $declined,
                ]);
                $result = $service->initiatePay($payment, null, BaseServiceHandler::STRING);
                $form = $result->isSuccess() ? (string) $result->getTemplate() : '';
            }
        }
        $this->arResult = [
            'PAID' => $order->isPaid(),
            'CANCELED' => $order->isCanceled(),
            'DECLINED' => $declined,
            'PAY_SYSTEM' => null !== $payment ? (string) $payment->getPaymentSystemName() : '',
            'SUM' => (int) round((float) $order->getPrice() * 100),
            'DATE_PAID' => null !== $payment && $payment->isPaid() && $payment->getField('DATE_PAID') instanceof Bitrix\Main\Type\DateTime
                ? $payment->getField('DATE_PAID')->format('d.m.Y H:i') : null,
            'INVOICE' => null !== $payment ? (string) $payment->getField('PS_INVOICE_ID') : '',
            'FORM' => $form,
        ];
        $this->includeComponentTemplate();
    }

    private function pay(int $orderId, bool $success): void
    {
        if (!check_bitrix_sessid()) {
            Flash::error('Сессия устарела. Повторите оплату.');
            $this->redirect(Url::order($orderId));
        }
        try {
            $outcome = new PaymentService()->process($orderId, Access::userId(), $success);
        } catch (PaymentException $e) {
            if (404 === $e->getCode()) {
                return;
            }
            Flash::error($e->getMessage());
            $this->redirect(Url::order($orderId));
        }
        if (PaymentService::PAID === $outcome) {
            Flash::success('Оплата прошла успешно.');
        } else {
            Flash::error('Оплата отклонена. Можно попробовать ещё раз.');
        }
        $this->redirect(Url::order($orderId));
    }
}
