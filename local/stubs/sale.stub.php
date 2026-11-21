<?php

/**
 * Минимальные стабы API 1С-Битрикс для PHPStan: модуль sale (сверены с sale 26.0.0).
 * Только то, что вызывает код /local и tests; тел у методов нет. Иерархия упрощена
 * (Order/Basket/Payment без промежуточных *Base), коллекции объявлены итерируемыми.
 */

namespace {
    class CSalePersonType
    {
        /**
         * @param array<string, mixed> $arFields
         *
         * @return int|false
         */
        public static function Add(array $arFields)
        {
        }
    }

    class CSaleOrderPropsGroup
    {
        /**
         * @param array<string, string>   $arOrder
         * @param array<array-key, mixed> $arFilter
         */
        public static function GetList(array $arOrder = [], array $arFilter = [], array|false $arGroupBy = false, array|false $arNavStartParams = false, array $arSelectFields = []): CDBResult
        {
        }

        /**
         * @param array<string, mixed> $arFields
         *
         * @return int|false
         */
        public static function Add(array $arFields)
        {
        }
    }

    class CSaleOrderProps
    {
        /**
         * @param array<string, mixed> $arFields
         *
         * @return int|false
         */
        public static function Add(array $arFields)
        {
        }
    }
}

namespace Bitrix\Sale {
    use Bitrix\Main\Result;

    class Registry
    {
        public const REGISTRY_TYPE_ORDER = 'ORDER';
    }

    class Fuser
    {
        /** @return int|null */
        public static function getId(bool $skipCreate = false)
        {
        }

        /** @return int|false */
        public static function getIdByUserId(int $userId)
        {
        }
    }

    abstract class BasketBase implements \IteratorAggregate, \Countable
    {
        /** @return \ArrayIterator<int, BasketItem> */
        public function getIterator(): \ArrayIterator
        {
        }

        public function count(): int
        {
        }

        /** @return list<BasketItem> */
        public function getBasketItems(): array
        {
        }

        public function getItemById(int|string $id): ?BasketItem
        {
        }

        public function getOrderableItems(): static
        {
        }

        public function save(): Result
        {
        }
    }

    class Basket extends BasketBase
    {
        public static function loadItemsForFUser(int $fUserId, string $siteId): static
        {
        }
    }

    class BasketItem
    {
        public function getId(): int
        {
        }

        public function getProductId(): int
        {
        }

        public function getQuantity(): float
        {
        }

        public function getPrice(): float
        {
        }

        public function canBuy(): bool
        {
        }

        public function isDelay(): bool
        {
        }

        /** @return string|int|float|bool|\Bitrix\Main\Type\DateTime|null */
        public function getField(string $name)
        {
        }

        public function setField(string $name, mixed $value): Result
        {
        }

        public function delete(): Result
        {
        }
    }

    class PropertyValue
    {
        /** @return string|int|float|bool|\Bitrix\Main\Type\DateTime|null */
        public function getField(string $name)
        {
        }

        public function getValue(): mixed
        {
        }

        public function setValue(mixed $value): Result
        {
        }
    }

    /** @implements \IteratorAggregate<int, PropertyValue> */
    class PropertyValueCollection implements \IteratorAggregate
    {
        /** @return \ArrayIterator<int, PropertyValue> */
        public function getIterator(): \ArrayIterator
        {
        }
    }

    class Payment
    {
        public function getId(): int
        {
        }

        /** @return string|int|float|bool|\Bitrix\Main\Type\DateTime|null */
        public function getField(string $name)
        {
        }

        public function setField(string $name, mixed $value): Result
        {
        }

        public function getSum(): float
        {
        }

        public function isPaid(): bool
        {
        }

        public function setPaid(string $value): Result
        {
        }

        public function getPaymentSystemId(): int
        {
        }

        public function getPaymentSystemName(): string
        {
        }

        public function getOrder(): Order
        {
        }
    }

    /** @implements \IteratorAggregate<int, Payment> */
    class PaymentCollection implements \IteratorAggregate
    {
        /** @return \ArrayIterator<int, Payment> */
        public function getIterator(): \ArrayIterator
        {
        }

        public function createItem(?PaySystem\Service $paySystem = null): Payment
        {
        }
    }

    class ShipmentItem
    {
        public function setQuantity(float $quantity): Result
        {
        }
    }

    class ShipmentItemCollection
    {
        public function createItem(BasketItem $basketItem): ?ShipmentItem
        {
        }
    }

    class Shipment
    {
        public function isSystem(): bool
        {
        }

        public function getDeliveryId(): int
        {
        }

        public function isReserved(): bool
        {
        }

        public function getShipmentItemCollection(): ShipmentItemCollection
        {
        }
    }

    /** @implements \IteratorAggregate<int, Shipment> */
    class ShipmentCollection implements \IteratorAggregate
    {
        /** @return \ArrayIterator<int, Shipment> */
        public function getIterator(): \ArrayIterator
        {
        }

        public function createItem(?Delivery\Services\Base $delivery = null): Shipment
        {
        }
    }

    class Order
    {
        public static function create(string $siteId, ?int $userId = null, ?string $currency = null): static
        {
        }

        /** @phpstan-impure */
        public static function load(int $id): ?static
        {
        }

        public static function deleteNoDemand(int $id): Result
        {
        }

        public function getId(): int
        {
        }

        public function getUserId(): int
        {
        }

        /** @return string|int|float|bool|\Bitrix\Main\Type\DateTime|null */
        public function getField(string $name)
        {
        }

        public function setField(string $name, mixed $value): Result
        {
        }

        public function getPrice(): float
        {
        }

        public function getCurrency(): string
        {
        }

        public function getPersonTypeId(): int
        {
        }

        public function setPersonTypeId(int $personTypeId): void
        {
        }

        public function isPaid(): bool
        {
        }

        public function isCanceled(): bool
        {
        }

        public function getBasket(): Basket
        {
        }

        public function setBasket(BasketBase $basket): Result
        {
        }

        public function getPropertyCollection(): PropertyValueCollection
        {
        }

        public function getShipmentCollection(): ShipmentCollection
        {
        }

        public function getPaymentCollection(): PaymentCollection
        {
        }

        public function save(): Result
        {
        }
    }
}

namespace Bitrix\Sale\Internals {
    use Bitrix\Main\ORM\Data\DataManager;

    class OrderTable extends DataManager
    {
    }

    class BasketTable extends DataManager
    {
    }

    class PaymentTable extends DataManager
    {
    }

    class OrderChangeTable extends DataManager
    {
    }

    class OrderPropsTable extends DataManager
    {
    }

    class PersonTypeTable extends DataManager
    {
    }

    class PaySystemActionTable extends DataManager
    {
    }

    class StatusTable extends DataManager
    {
        public const TYPE_ORDER = 'O';
    }

    class StatusLangTable extends DataManager
    {
    }

    class StatusGroupTaskTable extends DataManager
    {
    }
}

namespace Bitrix\Sale\Delivery\Services {
    abstract class Base
    {
    }

    class Configurable extends Base
    {
    }

    class Table extends \Bitrix\Main\ORM\Data\DataManager
    {
    }

    class Manager
    {
        public static function getObjectById(int $deliveryId): ?Base
        {
        }

        /** @param array<string, mixed> $fields */
        public static function add(array $fields): \Bitrix\Main\ORM\Data\AddResult
        {
        }
    }
}

namespace Bitrix\Sale\PaySystem {
    use Bitrix\Main\Request;
    use Bitrix\Sale\Payment;

    class ServiceResult extends \Bitrix\Main\Result
    {
        public const MONEY_COMING = 'money_coming';

        public function setOperationType(string $operationType): void
        {
        }

        /** @param array<string, mixed> $psData */
        public function setPsData(array $psData): void
        {
        }

        public function getTemplate(): string
        {
        }
    }

    abstract class BaseServiceHandler
    {
        public const STREAM = 1;
        public const STRING = 2;

        /** @return ServiceResult */
        abstract public function initiatePay(Payment $payment, ?Request $request = null);

        /** @return array<array-key, mixed> */
        abstract public function getCurrencyList();

        /** @return ServiceResult */
        public function showTemplate(?Payment $payment = null, string $template = '')
        {
        }
    }

    abstract class ServiceHandler extends BaseServiceHandler
    {
        /** @return ServiceResult */
        abstract public function processRequest(Payment $payment, Request $request);

        /** @return int|string|null */
        abstract public function getPaymentIdFromRequest(Request $request);

        /** @return array<array-key, mixed> */
        public static function getIndicativeFields()
        {
        }
    }

    class Service
    {
        /** @param array<string, mixed> $params */
        public function setTemplateParams(array $params): void
        {
        }

        public function initiatePay(Payment $payment, ?Request $request = null, int $mode = BaseServiceHandler::STREAM): ServiceResult
        {
        }

        public function processRequest(Request $request): ServiceResult
        {
        }
    }

    class Manager
    {
        public static function getObjectById(int $id): ?Service
        {
        }

        /** @return array<string, mixed>|false */
        public static function searchByRequest(Request $request)
        {
        }

        /** @param array<string, mixed> $fields */
        public static function add(array $fields): \Bitrix\Main\ORM\Data\AddResult
        {
        }

        /** @param array<string, mixed> $fields */
        public static function update(int $primary, array $fields): \Bitrix\Main\ORM\Data\UpdateResult
        {
        }
    }
}
