<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Controller;

use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\ActionFilter\HttpMethod;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\Response\Redirect;
use Bitrix\Main\Error;
use Bitrix\Main\Response;
use Dsefremov\ShopClassic\Security\Access;

/**
 * Базовый контроллер действий магазина: только POST, CSRF-токен ядра (sessid),
 * после действия — редирект (PRG). Ошибки доступа и CSRF превращаются в HTTP-статусы.
 */
abstract class BaseController extends Controller
{
    public const string ERROR_FORBIDDEN = 'forbidden';
    public const string ERROR_NOT_FOUND = 'not_found';

    protected function getDefaultPreFilters(): array
    {
        return [
            new HttpMethod([HttpMethod::METHOD_POST]),
            new Csrf(),
        ];
    }

    public function finalizeResponse(Response $response): void
    {
        $status = null;
        foreach ($this->getErrors() as $error) {
            $status ??= match ($error->getCode()) {
                Csrf::ERROR_INVALID_CSRF => '400 Bad Request',
                HttpMethod::ERROR_INVALID_HTTP_METHOD => '405 Method Not Allowed',
                self::ERROR_FORBIDDEN => '403 Forbidden',
                self::ERROR_NOT_FOUND => '404 Not Found',
                default => '422 Unprocessable Content',
            };
        }
        if (null !== $status && $response instanceof \Bitrix\Main\HttpResponse) {
            $response->setStatus($status);
        }
    }

    protected function redirect(string $url): Redirect
    {
        return new Redirect($url);
    }

    /**
     * Назад на страницу того же сайта (Referer), иначе — на $fallback.
     */
    protected function back(string $fallback = '/'): Redirect
    {
        $server = $this->getRequest()->getServer();
        $referer = \is_string($server->get('HTTP_REFERER')) ? $server->get('HTTP_REFERER') : '';
        $host = ($this->getRequest()->isHttps() ? 'https://' : 'http://').$server->getHttpHost();
        if ('' !== $referer && str_starts_with($referer, $host.'/')) {
            return new Redirect(substr($referer, \strlen($host)));
        }

        return new Redirect($fallback);
    }

    protected function notFound(string $message = 'Не найдено.'): null
    {
        $this->addError(new Error($message, self::ERROR_NOT_FOUND));

        return null;
    }

    protected function forbidden(string $message = 'Доступ запрещён.'): null
    {
        $this->addError(new Error($message, self::ERROR_FORBIDDEN));

        return null;
    }

    /**
     * Гость -> на страницу входа (с возвратом на $back).
     */
    protected function requireUser(string $back): ?Redirect
    {
        return Access::isAuthorized() ? null : new Redirect(Access::loginUrl($back));
    }

    protected function postString(string $key): string
    {
        $value = $this->getRequest()->getPost($key);

        return \is_string($value) ? $value : '';
    }

    protected function queryInt(string $key): int
    {
        $value = $this->getRequest()->getQuery($key);

        return \is_string($value) && ctype_digit($value) ? (int) $value : 0;
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function post(): array
    {
        return $this->getRequest()->getPostList()->toArray();
    }
}
