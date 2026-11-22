<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Fixtures;

use Bitrix\Main\Application;

/**
 * Пароли демо-учёток: генерируются случайно при первой загрузке демо-данных и хранятся
 * в var/demo-users.json (каталог var/ вне git, права 600). В коде и в выводе паролей нет.
 * Повторная загрузка демо-данных берёт пароли из файла, поэтому они не меняются.
 */
final class Credentials
{
    public const string FILE = '/var/demo-users.json';

    /**
     * @return array<string, array{email: string, password: string}>
     */
    public static function load(): array
    {
        $path = self::path();
        if (!is_file($path)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($path), true);
        $result = [];
        if (\is_array($data)) {
            foreach ($data as $key => $row) {
                if (\is_string($key) && \is_array($row) && \is_string($row['email'] ?? null) && \is_string($row['password'] ?? null)) {
                    $result[$key] = ['email' => $row['email'], 'password' => $row['password']];
                }
            }
        }

        return $result;
    }

    /**
     * Пароль учётки $key: из файла, а если его там нет — новый случайный (файл дописывается).
     */
    public static function password(string $key, string $email): string
    {
        $all = self::load();
        if (isset($all[$key]) && $all[$key]['email'] === $email) {
            return $all[$key]['password'];
        }
        $all[$key] = ['email' => $email, 'password' => self::generate()];
        $path = self::path();
        if (!is_dir(\dirname($path))) {
            mkdir(\dirname($path), 0o700, true);
        }
        file_put_contents($path, json_encode($all, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR)."\n", \LOCK_EX);
        chmod($path, 0o600);

        return $all[$key]['password'];
    }

    public static function generate(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(18)), '+/', 'Ab'), '=').'-9a';
    }

    private static function path(): string
    {
        return rtrim(Application::getDocumentRoot(), '/').self::FILE;
    }
}
