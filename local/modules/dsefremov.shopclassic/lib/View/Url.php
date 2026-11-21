<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\View;

use Bitrix\Main\Context;

/**
 * Адреса страниц и действий (действия форм — контроллеры модуля через ajax.php ядра).
 */
final class Url
{
    public const string AJAX = '/bitrix/services/main/ajax.php';

    /**
     * Действие контроллера: action('cart.add') -> /bitrix/services/main/ajax.php?action=dsefremov:shopclassic.cart.add.
     *
     * @param array<string, int|string> $params
     */
    public static function action(string $action, array $params = []): string
    {
        // Имена контроллеров — с заглавной буквы: тогда Engine\Resolver не приводит путь класса
        // к нижнему регистру и автозагрузчик находит lib/Controller/Admin/Product.php (PSR-4).
        $parts = explode('.', $action);
        $method = array_pop($parts);
        $name = implode('.', [...array_map(ucfirst(...), $parts), $method]);

        return self::AJAX.'?'.http_build_query(['action' => 'dsefremov:shopclassic.'.$name] + $params, '', '&', \PHP_QUERY_RFC3986);
    }

    public static function product(string $code): string
    {
        return '/products/'.rawurlencode($code).'/';
    }

    public static function order(int $id): string
    {
        return '/personal/orders/'.$id.'/';
    }

    /**
     * Адрес текущей страницы с изменёнными GET-параметрами (null — убрать параметр).
     * `page` сбрасывается, если его не передали явно: смена фильтра ведёт на первую страницу.
     *
     * @param array<string, scalar|null>   $updates
     * @param array<array-key, mixed>|null $query   текущие GET-параметры (по умолчанию — из запроса)
     */
    public static function list(array $updates, ?array $query = null, ?string $path = null): string
    {
        $request = Context::getCurrent()->getRequest();
        $query ??= $request->getQueryList()->toArray();
        $path ??= (string) parse_url((string) $request->getRequestUri(), \PHP_URL_PATH);
        if (!\array_key_exists('page', $updates)) {
            unset($query['page']);
        }
        foreach ($updates as $key => $value) {
            if (null === $value || '' === $value || (1 === $value && ('page' === $key || str_starts_with($key, 'PAGEN_')))) {
                unset($query[$key]);
            } else {
                $query[$key] = (string) $value;
            }
        }
        $qs = http_build_query($query, '', '&', \PHP_QUERY_RFC3986);

        return $path.('' !== $qs ? '?'.$qs : '');
    }

    /**
     * Текущий адрес (путь + query) — для возврата после входа.
     */
    public static function current(): string
    {
        return (string) Context::getCurrent()->getRequest()->getRequestUri();
    }
}
