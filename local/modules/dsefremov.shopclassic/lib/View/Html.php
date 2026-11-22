<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\View;

use Dsefremov\ShopClassic\Service\Flash;

/**
 * Общие фрагменты разметки шаблонов (аналог макросов Twig в symphony-shop).
 */
final class Html
{
    public static function e(string|int|float|null $value): string
    {
        return htmlspecialchars((string) $value, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Точка монтирования React-компонента: <div data-react-component="Name" data-props='{...}'>.
     *
     * @param array<string, mixed> $props
     */
    public static function react(string $component, array $props = [], string $class = ''): string
    {
        $json = json_encode($props, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR | \JSON_HEX_TAG | \JSON_HEX_AMP);

        return '<div data-react-component="'.self::e($component).'" data-props="'.self::e($json).'"'
            .('' !== $class ? ' class="'.self::e($class).'"' : '').'></div>';
    }

    /**
     * Флеш-сообщения после редиректа (вызывается отложенно из шаблона сайта).
     */
    public static function flashes(): string
    {
        $html = '';
        foreach (Flash::pull() as $flash) {
            $error = 'error' === $flash['type'];
            $html .= '<div role="'.($error ? 'alert' : 'status').'" data-testid="flash-'.self::e($flash['type']).'"'
                .' class="mb-4 rounded-lg border px-4 py-3 text-sm '.($error ? 'border-red-200 bg-red-50 text-red-800' : 'border-emerald-200 bg-emerald-50 text-emerald-800').'">'
                .self::e($flash['message']).'</div>';
        }

        return $html;
    }

    /**
     * Карточка «не найдено» для страниц с ответом 404.
     */
    public static function notFound(string $title = 'Страница не найдена', string $text = 'Такой страницы нет или она была удалена.'): string
    {
        return '<div class="card mx-auto max-w-lg p-10 text-center" data-testid="error-page">'
            .'<p class="text-5xl font-semibold text-zinc-300">404</p>'
            .'<h1 class="mt-2 text-xl font-semibold">'.self::e($title).'</h1>'
            .'<p class="mt-2 text-sm text-zinc-500">'.self::e($text).'</p>'
            .'<a href="/" class="btn btn-outline mt-6">В каталог</a></div>';
    }

    public static function csrf(): string
    {
        return '<input type="hidden" name="sessid" value="'.self::e(bitrix_sessid()).'">';
    }

    /**
     * Бейдж статуса заказа (tone: success / info / danger / muted / warning).
     */
    public static function statusBadge(string $label, string $tone): string
    {
        $class = match ($tone) {
            'success' => 'bg-emerald-100 text-emerald-800',
            'info' => 'bg-sky-100 text-sky-800',
            'danger' => 'bg-red-100 text-red-800',
            'muted' => 'bg-zinc-200 text-zinc-700',
            default => 'bg-amber-100 text-amber-800',
        };

        return '<span class="badge '.$class.'" data-testid="order-status">'.self::e($label).'</span>';
    }

    public static function image(?int $fileId, string $alt, string $class): string
    {
        $url = null !== $fileId && $fileId > 0 ? \CFile::GetPath($fileId) : null;
        if (!\is_string($url) || '' === $url) {
            return '<div class="'.self::e($class).' flex items-center justify-center bg-zinc-100 text-xs text-zinc-400">Нет фото</div>';
        }

        return '<img src="'.self::e($url).'" alt="'.self::e($alt).'" loading="lazy" class="'.self::e($class).' object-cover">';
    }

    /**
     * Пагинация: «← Назад», номера страниц (с многоточием), «Вперёд →». $param — GET-параметр страницы
     * (у компонентов ядра — PAGEN_N постраничной навигации).
     */
    public static function pagination(int $current, int $last, string $param = 'page'): string
    {
        if ($last <= 1) {
            return '';
        }
        $url = static fn (int $n): string => Url::list([$param => 1 === $n ? null : $n]);
        $html = '<nav class="mt-6 flex flex-wrap items-center justify-center gap-1" aria-label="Страницы">';
        $html .= $current > 1
            ? '<a href="'.self::e($url($current - 1)).'" class="btn btn-outline btn-sm" rel="prev">← Назад</a>'
            : '<span class="btn btn-outline btn-sm opacity-50" aria-disabled="true">← Назад</span>';
        for ($n = 1; $n <= $last; ++$n) {
            if (1 === $n || $n === $last || ($n >= $current - 1 && $n <= $current + 1)) {
                $html .= $n === $current
                    ? '<span class="btn btn-primary btn-sm min-w-8" aria-current="page">'.$n.'</span>'
                    : '<a href="'.self::e($url($n)).'" class="btn btn-outline btn-sm min-w-8">'.$n.'</a>';
            } elseif ($n === $current - 2 || $n === $current + 2) {
                $html .= '<span class="px-1 text-zinc-400">…</span>';
            }
        }
        $html .= $current < $last
            ? '<a href="'.self::e($url($current + 1)).'" class="btn btn-outline btn-sm" rel="next">Вперёд →</a>'
            : '<span class="btn btn-outline btn-sm opacity-50" aria-disabled="true">Вперёд →</span>';

        return $html.'</nav>';
    }

    public static function noResults(string $resetUrl, string $text = 'Попробуйте изменить запрос или фильтры.'): string
    {
        return '<div class="card flex flex-col items-center gap-3 p-10 text-center" data-testid="no-results">'
            .'<p class="font-medium">Ничего не найдено</p>'
            .'<p class="text-sm text-zinc-500">'.self::e($text).'</p>'
            .'<a href="'.self::e($resetUrl).'" class="btn btn-outline" data-testid="no-results-reset">Сбросить</a></div>';
    }

    /**
     * Вкладки статуса над списком заказов.
     *
     * @param list<array{value: ?string, label: string}> $tabs
     */
    public static function statusTabs(array $tabs, ?string $active): string
    {
        $html = '<div class="-mx-1 flex gap-1 overflow-x-auto px-1 pb-1" role="tablist" aria-label="Статус">';
        foreach ($tabs as $tab) {
            $isActive = $active === $tab['value'];
            $html .= '<a href="'.self::e(Url::list(['status' => $tab['value']])).'" role="tab" aria-selected="'.($isActive ? 'true' : 'false').'"'
                .' class="shrink-0 rounded-full border px-3 py-1 text-sm '.($isActive ? 'border-zinc-900 bg-zinc-900 text-white' : 'bg-white hover:bg-zinc-50').'">'
                .self::e($tab['label']).'</a>';
        }

        return $html.'</div>';
    }

    /**
     * Ошибка поля формы.
     *
     * @param array<string, string> $errors
     */
    public static function fieldError(array $errors, string $field): string
    {
        return isset($errors[$field])
            ? '<p class="mt-1 text-sm text-red-600" data-testid="error-'.self::e($field).'">'.self::e($errors[$field]).'</p>'
            : '';
    }

    /**
     * Ошибка формы целиком (например, нехватка остатка при оформлении).
     *
     * @param array<string, string> $errors
     */
    public static function formError(array $errors): string
    {
        return isset($errors['_form'])
            ? '<div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert" data-testid="form-error">'.self::e($errors['_form']).'</div>'
            : '';
    }

    /**
     * Строка формы: подпись, поле (input / textarea / select), подсказка и ошибка.
     *
     * @param array<string, string>     $errors
     * @param array<string, string|int> $attrs
     * @param array<string|int, string> $options для select: значение => подпись
     */
    public static function field(
        string $type,
        string $name,
        string $label,
        string $value,
        array $errors = [],
        array $attrs = [],
        array $options = [],
        string $help = '',
    ): string {
        $id = 'f-'.str_replace('_', '-', $name);
        $invalid = isset($errors[$name]);
        $attr = '';
        foreach ($attrs as $key => $attrValue) {
            $attr .= ' '.self::e($key).'="'.self::e($attrValue).'"';
        }
        if ($invalid) {
            $attr .= ' aria-invalid="true"';
        }
        if ('' !== $help) {
            $attr .= ' aria-describedby="'.$id.'-help"';
        }
        $control = match ($type) {
            'textarea' => '<textarea id="'.$id.'" name="'.self::e($name).'" class="input"'.$attr.'>'.self::e($value).'</textarea>',
            'select' => '<select id="'.$id.'" name="'.self::e($name).'" class="input"'.$attr.'>'
                .implode('', array_map(
                    static fn (string|int $optionValue, string $optionLabel): string => '<option value="'.self::e($optionValue).'"'
                        .((string) $optionValue === $value ? ' selected' : '').'>'.self::e($optionLabel).'</option>',
                    array_keys($options),
                    $options,
                )).'</select>',
            'file' => '<input type="file" id="'.$id.'" name="'.self::e($name).'"'.$attr.'>',
            default => '<input type="'.self::e($type).'" id="'.$id.'" name="'.self::e($name).'" value="'.self::e($value).'" class="input"'.$attr.'>',
        };

        return '<div class="space-y-1.5"><label for="'.$id.'" class="label">'.self::e($label).'</label>'.$control
            .('' !== $help ? '<p id="'.$id.'-help" class="text-xs text-zinc-500">'.self::e($help).'</p>' : '')
            .($invalid ? '<p class="text-sm text-red-600" role="alert" data-testid="error-'.self::e($name).'">'.self::e($errors[$name]).'</p>' : '')
            .'</div>';
    }
}
