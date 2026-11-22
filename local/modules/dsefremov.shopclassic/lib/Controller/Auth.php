<?php

declare(strict_types=1);

namespace Dsefremov\ShopClassic\Controller;

use Bitrix\Main\Engine\Response\Redirect;
use Dsefremov\ShopClassic\Security\Access;
use Dsefremov\ShopClassic\Service\Flash;
use Dsefremov\ShopClassic\Service\UserService;

/**
 * Вход, регистрация и выход на CUser ядра. Логин пользователя = эл. почта.
 */
final class Auth extends BaseController
{
    public const string LOGIN_FORM = 'login';
    public const string REGISTER_FORM = 'register';

    public function loginAction(): Redirect
    {
        $back = Access::safeBackUrl($this->postString('back'));
        if (Access::isAuthorized()) {
            return $this->redirect($back);
        }
        $email = trim($this->postString('email'));
        if (!new UserService()->login($email, $this->postString('password'), 'Y' === $this->postString('remember'))) {
            Flash::form(self::LOGIN_FORM, ['_form' => 'Неверная эл. почта или пароль.'], ['email' => $email]);

            return $this->redirect('/login/'.('/' !== $back ? '?back='.rawurlencode($back) : ''));
        }

        return $this->redirect($back);
    }

    public function registerAction(): Redirect
    {
        if (Access::isAuthorized()) {
            return $this->redirect('/');
        }
        $users = new UserService();
        $result = $users->register($this->post());
        if (\is_array($result)) {
            Flash::form(self::REGISTER_FORM, $result, $this->post());

            return $this->redirect('/register/');
        }
        $users->authorize($result);

        return $this->redirect('/personal/orders/');
    }

    public function logoutAction(): Redirect
    {
        new UserService()->logout();

        return $this->redirect('/');
    }
}
