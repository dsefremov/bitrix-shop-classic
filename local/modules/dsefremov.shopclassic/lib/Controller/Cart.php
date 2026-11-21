<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Controller;

use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Engine\Response\Redirect;
use Dsefremov\ShopClassic\Config;
use Dsefremov\ShopClassic\Entity\Row;
use Dsefremov\ShopClassic\Service\BasketService;
use Dsefremov\ShopClassic\Service\Flash;

/**
 * Действия корзины (Sale\Basket покупателя): добавить с возвратом на страницу, изменить количество,
 * удалить строку. Только POST с sessid, ответ — редирект (PRG) с флеш-сообщением.
 */
final class Cart extends BaseController
{
    public function addAction(): ?Redirect
    {
        $productId = filter_var($this->postString('product_id'), \FILTER_VALIDATE_INT);
        $name = false !== $productId ? $this->productName($productId) : null;
        if (false === $productId || null === $name) {
            return $this->notFound('Товар не найден.');
        }
        $raw = $this->postString('quantity');
        $quantity = filter_var('' === $raw ? '1' : $raw, \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
        if (false === $quantity) {
            Flash::error('Количество должно быть от 1 до 1000.');

            return $this->back();
        }
        $result = new BasketService()->add($productId, $quantity);
        if (!$result->isSuccess()) {
            Flash::error("«{$name}»: ".implode(' ', $result->getErrorMessages()));
        } elseif (true === ($result->getData()['limited'] ?? false)) {
            $available = $result->getData()['available'] ?? 0;
            $available = is_numeric($available) ? (int) $available : 0;
            Flash::error("Доступно только {$available} шт. товара «{$name}», в корзину добавлен весь остаток.");
        } else {
            Flash::success("«{$name}» добавлен в корзину.");
        }

        return $this->back();
    }

    public function updateAction(): ?Redirect
    {
        $quantity = filter_var($this->postString('quantity'), \FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 1000]]);
        if (false === $quantity) {
            Flash::error('Количество должно быть от 0 до 1000.');

            return $this->redirect('/cart/');
        }
        $result = new BasketService()->setQuantity($this->queryInt('itemId'), $quantity);
        if (!$result->isSuccess()) {
            if ('not_found' === $result->getErrorCollection()->current()?->getCode()) {
                return $this->notFound('Товара нет в корзине.');
            }
            Flash::error(implode(' ', $result->getErrorMessages()));
        }

        return $this->redirect('/cart/');
    }

    public function removeAction(): ?Redirect
    {
        $result = new BasketService()->remove($this->queryInt('itemId'));
        if (!$result->isSuccess()) {
            return $this->notFound('Товара нет в корзине.');
        }
        Flash::success('Товар удалён из корзины.');

        return $this->redirect('/cart/');
    }

    private function productName(int $productId): ?string
    {
        $row = ElementTable::getList([
            'select' => ['NAME'],
            'filter' => ['=ID' => $productId, '=IBLOCK_ID' => Config::iblockId(), '=ACTIVE' => 'Y'],
        ])->fetch();

        return \is_array($row) ? Row::string($row, 'NAME') : null;
    }
}
