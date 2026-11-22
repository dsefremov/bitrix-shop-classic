<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Service;

use Bitrix\Main\Application;

/**
 * Одноразовые сообщения после редиректа (PRG) и состояние форм (ошибки + введённые значения).
 */
final class Flash
{
    private const string KEY = 'DSEFREMOV_SHOP_FLASH';
    private const string FORM_KEY = 'DSEFREMOV_SHOP_FORM';

    /** Сообщения не выводить на этой странице: запрос завершается редиректом. */
    private static bool $kept = false;

    public static function keepForNextRequest(): void
    {
        self::$kept = true;
    }

    public static function success(string $message): void
    {
        self::add('success', $message);
    }

    public static function error(string $message): void
    {
        self::add('error', $message);
    }

    /**
     * @return list<array{type: string, message: string}>
     */
    public static function pull(): array
    {
        if (self::$kept) {
            return [];
        }
        $session = Application::getInstance()->getSession();
        if (!$session->has(self::KEY)) {
            return [];
        }
        $messages = $session->get(self::KEY);
        $session->remove(self::KEY);
        if (!\is_array($messages)) {
            return [];
        }
        $result = [];
        foreach ($messages as $message) {
            if (\is_array($message) && \is_string($message['type'] ?? null) && \is_string($message['message'] ?? null)) {
                $result[] = ['type' => $message['type'], 'message' => $message['message']];
            }
        }

        return $result;
    }

    /**
     * Сохраняет ошибки и введённые значения формы до следующего GET.
     *
     * @param array<string, string>   $errors
     * @param array<array-key, mixed> $old
     */
    public static function form(string $form, array $errors, array $old = []): void
    {
        $session = Application::getInstance()->getSession();
        $forms = $session->get(self::FORM_KEY);
        $forms = \is_array($forms) ? $forms : [];
        $clean = [];
        foreach ($old as $key => $value) {
            if (\is_string($key) && \is_string($value) && !str_contains($key, 'password')) {
                $clean[$key] = $value;
            }
        }
        $forms[$form] = ['errors' => $errors, 'old' => $clean];
        $session->set(self::FORM_KEY, $forms);
    }

    /**
     * @return array{errors: array<string, string>, old: array<string, string>}
     */
    public static function pullForm(string $form): array
    {
        $session = Application::getInstance()->getSession();
        $forms = $session->has(self::FORM_KEY) ? $session->get(self::FORM_KEY) : null;
        if (!\is_array($forms) || !isset($forms[$form]) || !\is_array($forms[$form])) {
            return ['errors' => [], 'old' => []];
        }
        $state = $forms[$form];
        unset($forms[$form]);
        $session->set(self::FORM_KEY, $forms);

        return [
            'errors' => self::stringMap($state['errors'] ?? []),
            'old' => self::stringMap($state['old'] ?? []),
        ];
    }

    private static function add(string $type, string $message): void
    {
        $session = Application::getInstance()->getSession();
        $messages = $session->get(self::KEY);
        $messages = \is_array($messages) ? $messages : [];
        $messages[] = ['type' => $type, 'message' => $message];
        $session->set(self::KEY, $messages);
    }

    /**
     * @return array<string, string>
     */
    private static function stringMap(mixed $value): array
    {
        $map = [];
        if (\is_array($value)) {
            foreach ($value as $key => $item) {
                if (\is_string($key) && \is_string($item)) {
                    $map[$key] = $item;
                }
            }
        }

        return $map;
    }
}
