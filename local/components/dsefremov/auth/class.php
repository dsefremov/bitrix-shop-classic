<?php

use Bitrix\Main\Loader;
use Dsefremov\ShopClassic\Component\ShopComponent;
use Dsefremov\ShopClassic\Controller\Auth;
use Dsefremov\ShopClassic\Security\Access;
use Dsefremov\ShopClassic\Service\Flash;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    exit;
}
Loader::includeModule('dsefremov.shopclassic');

/**
 * Вход / регистрация на CUser. Параметр MODE: login | register. Формы отправляются в контроллер Auth.
 */
final class DsefremovAuthComponent extends ShopComponent
{
    public function executeComponent(): void
    {
        $mode = \is_string($this->arParams['MODE'] ?? null) ? $this->arParams['MODE'] : 'login';
        'register' === $mode ? $this->showRegister() : $this->showLogin();
    }

    private function showLogin(): void
    {
        $back = Access::safeBackUrl(\is_string($this->query()['back'] ?? null) ? $this->query()['back'] : null);
        if (Access::isAuthorized()) {
            $this->redirect($back);
        }
        $form = Flash::pullForm(Auth::LOGIN_FORM);
        $this->arResult = ['BACK' => $back, 'ERRORS' => $form['errors'], 'VALUES' => $form['old']];
        $this->setTitle('Вход');
        $this->includeComponentTemplate('login');
    }

    private function showRegister(): void
    {
        if (Access::isAuthorized()) {
            $this->redirect('/');
        }
        $form = Flash::pullForm(Auth::REGISTER_FORM);
        $this->arResult = ['ERRORS' => $form['errors'], 'VALUES' => $form['old']];
        $this->setTitle('Регистрация');
        $this->includeComponentTemplate('register');
    }
}
