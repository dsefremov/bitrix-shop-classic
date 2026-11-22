<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Service;

use Dsefremov\ShopClassic\Config;

/**
 * GET-параметры каталога, которых нет у штатных компонентов: поиск q (название и описание),
 * сортировка sort/dir (название, цена, новизна) и «только в наличии» available.
 * Всё проходит белые списки и превращается в параметры bitrix:catalog / дополнение фильтра arrFilter.
 * Цена от/до — штатный bitrix:catalog.smart.filter (arrFilter_P{id}_MIN/MAX + set_filter=y).
 */
final readonly class CatalogQuery
{
    /** @var array<string, string> */
    public const array SORTS = ['name' => 'name', 'price' => 'price', 'newest' => 'newest'];
    public const string DEFAULT_SORT = 'name';
    public const string DEFAULT_DIR = 'asc';

    public function __construct(
        public ?string $q,
        public string $sort,
        public string $dir,
        public bool $available,
    ) {
    }

    /**
     * @param array<array-key, mixed> $query
     */
    public static function fromQuery(array $query): self
    {
        $q = \is_string($query['q'] ?? null) ? trim($query['q']) : '';
        $sort = \is_string($query['sort'] ?? null) && isset(self::SORTS[$query['sort']]) ? $query['sort'] : self::DEFAULT_SORT;
        $dir = \in_array($query['dir'] ?? null, ['asc', 'desc'], true) ? (string) $query['dir'] : self::DEFAULT_DIR;
        $available = \in_array($query['available'] ?? null, ['1', 'Y', 'y', 'on'], true);

        return new self('' !== $q ? mb_substr($q, 0, 100) : null, $sort, $dir, $available);
    }

    /**
     * Поле сортировки CIBlockElement::GetList для ELEMENT_SORT_FIELD (вторичная — ID).
     */
    public function sortField(): string
    {
        return match ($this->sort) {
            'price' => 'CATALOG_PRICE_'.Config::priceTypeId(),
            'newest' => 'created',
            default => 'name',
        };
    }

    /**
     * Направление: для новизны «сначала новые» = desc и вторичная сортировка по ID в ту же сторону.
     */
    public function sortOrder(): string
    {
        return $this->dir;
    }

    /**
     * Условие поиска для фильтра CIBlockElement: подстрока в названии или в описаниях.
     * Символы % и _ ищутся буквально (оператор % экранирует их сам, проверено тестом).
     *
     * @return array<array-key, mixed>|null
     */
    public function searchFilter(): ?array
    {
        if (null === $this->q) {
            return null;
        }

        return ['LOGIC' => 'OR', '%NAME' => $this->q, '%PREVIEW_TEXT' => $this->q, '%DETAIL_TEXT' => $this->q];
    }

    /**
     * Текущие значения для React-островов (SearchBox, SortSelect, FilterPanel).
     *
     * @return array{q: ?string, sort: string, dir: string, available: ?string}
     */
    public function state(): array
    {
        return ['q' => $this->q, 'sort' => $this->sort, 'dir' => $this->dir, 'available' => $this->available ? '1' : null];
    }
}
