<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Install;

/**
 * Миграция модуля. up() идемпотентна: ищет сущность по символьному коду / XML_ID и создаёт её,
 * только если её ещё нет, поэтому повторная установка и повторный запуск безопасны.
 */
interface Migration
{
    public function name(): string;

    public function up(): void;
}
