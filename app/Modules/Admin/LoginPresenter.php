<?php

declare(strict_types=1);

namespace App\Modules\Admin;

use App\Components\LoginForm\LoginForm;
use Nette;
use Tracy\ILogger;

final class LoginPresenter extends Nette\Application\UI\Presenter {

    protected ILogger $logger;

    public function __construct(ILogger $logger) {
        parent::__construct();
        $this->logger = $logger;
    }

    public function startup(): void {
        parent::startup();

        if ($this->getHttpRequest()->getMethod() === 'OPTIONS') {
            $this->getHttpResponse()->setCode(204);
            $this->terminate();
        }
    }

    public function beforeRender(): void {
        if ($this->user->isLoggedIn()) {
            $this->redirect('Admin:default');
        }
    }

    public function renderDefault(): void {
    }

    public function actionOut(): void {
        $email = $this->user->getIdentity()->getData()['email'];
        $this->logger->log($email . ' logged out.', ILogger::INFO);
        $this->getUser()->logout();
        $this->flashMessage('Odhlášení bylo úspěšné.', 'alert-success');
        $this->redirect('Login:default');
    }

    protected function createComponentLoginForm(): LoginForm {
        return new LoginForm($this->getUser(), $this->logger);
    }
}
