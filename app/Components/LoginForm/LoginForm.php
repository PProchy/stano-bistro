<?php

declare(strict_types=1);

namespace App\Components\LoginForm;

use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nette\Security\AuthenticationException;
use Nette\Utils\ArrayHash;
use Tracy\ILogger;

class LoginForm extends Control
{
    private User $user;
    private ILogger $logger;

    public function __construct(User $user, ILogger $logger)
    {
        $this->user = $user;
        $this->logger = $logger;
    }

    public function render(): void
    {
        $this->template->render(__DIR__ . '/loginForm.latte');
    }

    protected function createComponentLoginForm(): Form
    {
        $form = new Form();

        $form->addText('email', 'Email')
            ->setRequired('Vyplňte email.');

        $form->addPassword('password', 'Heslo')
            ->setRequired('Vyplňte heslo.');

        $form->addSubmit('login', 'Přihlásit se');

        $form->onSuccess[] = [$this, 'processLogin'];

        return $form;
    }

    public function processLogin(Form $form, ArrayHash $values): void
    {
        try {
            $this->user->login($values->email, $values->password);
            $email = $this->user->getIdentity()->getData()['email'];
            $this->logger->log($email . ' logged in.', ILogger::INFO);
            $this->getPresenter()->flashMessage('Přihlášení bylo úspěšné.', 'alert-success');
            $this->getPresenter()->redirect('Admin:default');
        } catch (AuthenticationException $e) {
            $form->addError('Nesprávné uživatelské jméno nebo heslo.');
        }
    }
}
