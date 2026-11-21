<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Component;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Dsefremov\ShopClassic\Security\Access;
use Dsefremov\ShopClassic\Service\Flash;
use Dsefremov\ShopClassic\View\Html;
use Dsefremov\ShopClassic\View\Url;

/**
 * Общая база своих компонентов магазина: ЧПУ, доступ, страницы 403/404, заголовок.
 *
 * @property array<string, mixed> $arParams
 * @property array<string, mixed> $arResult
 */
abstract class ShopComponent extends \CBitrixComponent
{
    /**
     * Разбор адреса комплексного компонента (SEF_FOLDER + SEF_URL_TEMPLATES).
     *
     * @return array{0: string, 1: array<string, string>} [страница, переменные]
     */
    protected function route(string $default = 'list'): array
    {
        $folder = \is_string($this->arParams['SEF_FOLDER'] ?? null) ? $this->arParams['SEF_FOLDER'] : '/';
        $templates = [];
        foreach (\is_array($this->arParams['SEF_URL_TEMPLATES'] ?? null) ? $this->arParams['SEF_URL_TEMPLATES'] : [] as $name => $template) {
            if (\is_string($name) && \is_string($template)) {
                $templates[$name] = $template;
            }
        }
        $variables = [];
        $engine = new \CComponentEngine($this);
        $page = $engine->guessComponentPath($folder, $templates, $variables);
        if (!\is_string($page) || '' === $page) {
            $requested = (string) parse_url((string) Context::getCurrent()->getRequest()->getRequestUri(), \PHP_URL_PATH);
            $page = rtrim($requested, '/') === rtrim($folder, '/') ? $default : '';
        }
        $clean = [];
        foreach ($variables as $key => $value) {
            if (\is_string($key) && \is_scalar($value)) {
                $clean[$key] = (string) $value;
            }
        }

        return [$page, $clean];
    }

    /**
     * Гость -> на вход с возвратом на текущую страницу. true — можно продолжать.
     */
    protected function requireUser(): bool
    {
        if (Access::isAuthorized()) {
            return true;
        }
        $this->redirect(Access::loginUrl(Url::current()));
    }

    /**
     * Редирект из компонента. Флеш-сообщения остаются в сессии до следующей страницы:
     * отложенный вывод в шаблоне сайта срабатывает и при LocalRedirect.
     *
     * @return never
     */
    protected function redirect(string $url): void
    {
        Flash::keepForNextRequest();
        LocalRedirect($url);
    }

    protected function showError(int $code, string $title, string $text): void
    {
        $status = match ($code) {
            403 => '403 Forbidden',
            404 => '404 Not Found',
            default => (string) $code,
        };
        \CHTTP::SetStatus($status);
        if (!\defined('ERROR_404') && 404 === $code) {
            \define('ERROR_404', 'Y');
        }
        $this->setTitle($title);
        echo 404 === $code ? Html::notFound($title, $text) : '<div class="card mx-auto max-w-lg p-10 text-center" data-testid="error-page">'
            .'<p class="text-5xl font-semibold text-zinc-300">'.$code.'</p>'
            .'<h1 class="mt-2 text-xl font-semibold">'.Html::e($title).'</h1>'
            .'<p class="mt-2 text-sm text-zinc-500">'.Html::e($text).'</p>'
            .'<a href="/" class="btn btn-outline mt-6">В каталог</a></div>';
    }

    protected function notFound(string $text = 'Такой страницы нет или она была удалена.'): void
    {
        $this->showError(404, 'Страница не найдена', $text);
    }

    protected function setTitle(string $title): void
    {
        global $APPLICATION;
        if ($APPLICATION instanceof \CMain) {
            $APPLICATION->SetTitle($title);
        }
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function query(): array
    {
        return Application::getInstance()->getContext()->getRequest()->getQueryList()->toArray();
    }
}
