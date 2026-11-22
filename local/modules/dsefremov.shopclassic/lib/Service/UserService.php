<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Service;

use Bitrix\Main\UserTable;
use Dsefremov\ShopClassic\Entity\Row;

/**
 * Пользователи — стандартные b_user ядра (CUser). Логин = эл. почта, имя — поле NAME.
 */
final class UserService
{
    /**
     * @param array<array-key, mixed> $input
     *
     * @return int|array<string, string> ID нового пользователя или ошибки по полям
     */
    public function register(array $input): int|array
    {
        $v = new Validator($input);
        $v->required('name')->max('name', 255)
            ->required('email')->email('email')->max('email', 255)
            ->required('password')->min('password', 8, 'Пароль — не меньше 8 символов.')->max('password', 4096);
        if ($v->string('password') !== $v->string('password_confirmation')) {
            $v->error('password_confirmation', 'Пароли не совпадают.');
        }
        $email = mb_strtolower($v->string('email'));
        if (false !== filter_var($email, \FILTER_VALIDATE_EMAIL) && null !== $this->findIdByEmail($email)) {
            $v->error('email', 'Пользователь с такой эл. почтой уже зарегистрирован.');
        }
        if ($v->fails()) {
            return $v->errors();
        }

        $user = new \CUser();
        $id = $user->Add([
            'LOGIN' => $email,
            'EMAIL' => $email,
            'NAME' => $v->string('name'),
            'PASSWORD' => $v->string('password'),
            'CONFIRM_PASSWORD' => $v->string('password'),
            'ACTIVE' => 'Y',
            'LID' => \Dsefremov\ShopClassic\Config::SITE_ID,
            'GROUP_ID' => [2],
        ]);
        if (!\is_numeric($id) || (int) $id <= 0) {
            return ['email' => trim(strip_tags(str_replace('<br>', ' ', (string) $user->LAST_ERROR))) ?: 'Не удалось зарегистрироваться.'];
        }

        return (int) $id;
    }

    public function authorize(int $userId): void
    {
        $this->cuser()->Authorize($userId);
    }

    /**
     * Вход по эл. почте и паролю. true — успешно.
     */
    public function login(string $email, string $password, bool $remember = false): bool
    {
        $email = mb_strtolower(trim($email));
        if ('' === $email || '' === $password) {
            return false;
        }
        $result = $this->cuser()->Login($email, $password, $remember ? 'Y' : 'N');

        return true === $result;
    }

    public function logout(): void
    {
        $this->cuser()->Logout();
    }

    public function findIdByEmail(string $email): ?int
    {
        $row = UserTable::getList([
            'select' => ['ID'],
            'filter' => ['=LOGIN' => mb_strtolower($email)],
            'limit' => 1,
        ])->fetch();

        return \is_array($row) ? Row::int($row, 'ID') : null;
    }

    /**
     * @return array{id: int, name: string, email: string}|null
     */
    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $row = UserTable::getList(['select' => ['ID', 'NAME', 'LAST_NAME', 'EMAIL'], 'filter' => ['=ID' => $id]])->fetch();
        if (!\is_array($row)) {
            return null;
        }
        $name = trim(Row::string($row, 'NAME').' '.Row::string($row, 'LAST_NAME'));

        return ['id' => Row::int($row, 'ID'), 'name' => '' !== $name ? $name : Row::string($row, 'EMAIL'), 'email' => Row::string($row, 'EMAIL')];
    }

    private function cuser(): \CUser
    {
        global $USER;
        if (!$USER instanceof \CUser) {
            $USER = new \CUser();
        }

        return $USER;
    }
}
