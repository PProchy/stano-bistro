<?php

declare(strict_types=1);

namespace App\Modules\Admin;

use Nette;
use App\Model\UserFacade;
use App\Model\MenuFacade;
use Tracy\ILogger;

class AdminPresenter extends Nette\Application\UI\Presenter {

    protected ILogger $logger;
    protected UserFacade $userFacade;
    protected MenuFacade $menuFacade;
    protected string $uploadDir;

    public function __construct(ILogger $logger, UserFacade $userFacade, MenuFacade $menuFacade, string $uploadDir) {
        parent::__construct();
        $this->logger = $logger;
        $this->userFacade = $userFacade;
        $this->menuFacade = $menuFacade;
        $this->uploadDir = $uploadDir;
    }

    public function startup(): void {
        parent::startup();

        if ($this->getHttpRequest()->getMethod() === 'OPTIONS') {
            $this->getHttpResponse()->setCode(204);
            $this->terminate();
        }

        // in startup() so the check runs before signals and forms are processed
        if (!$this->user->isLoggedIn()) {
            $this->flashMessage('Prosím přihlašte se!', 'alert-warning');
            $this->redirect('Login:default');
        }
    }

    protected function getUserEmail(): string {
        return $this->user->getIdentity()->getData()['email'];
    }
}
