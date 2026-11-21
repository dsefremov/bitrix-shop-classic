<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Service;

/**
 * Простая проверка полей формы: накапливает ошибки по именам полей.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /**
     * @param array<array-key, mixed> $input
     */
    public function __construct(private readonly array $input)
    {
    }

    public function string(string $field): string
    {
        $value = $this->input[$field] ?? '';

        return \is_string($value) ? trim($value) : '';
    }

    public function required(string $field, string $message = 'Заполните поле.'): self
    {
        if ('' === $this->string($field)) {
            $this->error($field, $message);
        }

        return $this;
    }

    public function max(string $field, int $max): self
    {
        if (mb_strlen($this->string($field)) > $max) {
            $this->error($field, "Не больше {$max} символов.");
        }

        return $this;
    }

    public function min(string $field, int $min, ?string $message = null): self
    {
        $value = $this->string($field);
        if ('' !== $value && mb_strlen($value) < $min) {
            $this->error($field, $message ?? "Не меньше {$min} символов.");
        }

        return $this;
    }

    public function email(string $field): self
    {
        $value = $this->string($field);
        if ('' !== $value && false === filter_var($value, \FILTER_VALIDATE_EMAIL)) {
            $this->error($field, 'Укажите корректный адрес эл. почты.');
        }

        return $this;
    }

    public function regex(string $field, string $pattern, string $message): self
    {
        $value = $this->string($field);
        if ('' !== $value && 1 !== preg_match($pattern, $value)) {
            $this->error($field, $message);
        }

        return $this;
    }

    public function error(string $field, string $message): self
    {
        $this->errors[$field] ??= $message;

        return $this;
    }

    public function fails(): bool
    {
        return [] !== $this->errors;
    }

    /**
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
