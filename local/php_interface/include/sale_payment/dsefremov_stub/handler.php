<?php

declare(strict_types=1);

namespace Sale\Handlers\PaySystem;

use Bitrix\Main\Error;
use Bitrix\Main\Request;
use Bitrix\Main\Type\DateTime;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaySystem;
use Dsefremov\ShopClassic\Config;

/**
 * Платёжная система «Тестовая оплата» — заглушка шлюза на штатном механизме платёжных систем sale.
 *
 * initiatePay() выводит шаблон template/template.php (кнопки «Оплатить» и «Отказ»).
 * processRequest() вызывается штатным PaySystem\Service::processRequest():
 *  - stub_result=success → операция MONEY_COMING: ядро само ставит оплату (Payment::setPaid('Y')),
 *    пишет её в историю заказа и меняет статус заказа на «Оплачен» (опция sale status_on_paid);
 *  - stub_result=fail → ошибка «Оплата отклонена»: ядро помечает оплату (MARKED = Y, маркер
 *    в b_sale_order_marker) и пишет это в историю заказа; заказ остаётся неоплаченным, резерв
 *    товара сохраняется, оплату можно повторить.
 * Внешний адрес-обработчик ответов шлюза (sale_ps_result.php) заглушку не принимает:
 * getIndicativeFields() пуст, вызывать её может только код магазина после проверки владельца заказа.
 */
class Dsefremov_StubHandler extends PaySystem\ServiceHandler
{
    public const string FIELD_PAYMENT_ID = 'PAYMENT_ID';
    public const string FIELD_RESULT = 'STUB_RESULT';
    public const string RESULT_SUCCESS = 'success';
    public const string RESULT_FAIL = 'fail';

    /**
     * @return PaySystem\ServiceResult
     */
    public function initiatePay(Payment $payment, ?Request $request = null)
    {
        return $this->showTemplate($payment, 'template');
    }

    /**
     * @return PaySystem\ServiceResult
     */
    public function processRequest(Payment $payment, Request $request)
    {
        $result = new PaySystem\ServiceResult();
        $now = new DateTime();
        if (self::RESULT_SUCCESS === $request->get(self::FIELD_RESULT)) {
            $result->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
            $result->setPsData([
                'PS_STATUS' => 'Y',
                'PS_STATUS_CODE' => 'paid',
                'PS_STATUS_DESCRIPTION' => 'Тестовая оплата проведена',
                'PS_STATUS_MESSAGE' => '',
                'PS_INVOICE_ID' => 'STUB-'.$payment->getId().'-'.$now->getTimestamp(),
                'PS_SUM' => $payment->getSum(),
                'PS_CURRENCY' => $payment->getField('CURRENCY'),
                'PS_RESPONSE_DATE' => $now,
                'MARKED' => 'N',
            ]);

            return $result;
        }

        $result->addError(new Error(Config::DECLINED_MESSAGE, 'DSEFREMOV_STUB_DECLINED'));

        return $result;
    }

    /**
     * @return int|string|null
     */
    public function getPaymentIdFromRequest(Request $request)
    {
        $id = $request->get(self::FIELD_PAYMENT_ID);

        return is_numeric($id) ? (int) $id : null;
    }

    /**
     * @return list<string>
     */
    public function getCurrencyList()
    {
        return [Config::CURRENCY];
    }

    /**
     * @return array<array-key, mixed>
     */
    public static function getIndicativeFields()
    {
        return [];
    }
}
