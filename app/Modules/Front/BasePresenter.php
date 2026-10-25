<?php

declare(strict_types=1);

namespace App\Modules\Front;

use App\Model\MenuFacade;
use Nette;
use Tracy\ILogger;

abstract class BasePresenter extends Nette\Application\UI\Presenter {

    protected ILogger $logger;
    protected MenuFacade $menuFacade;

    public function __construct(MenuFacade $menuFacade, ILogger $logger) {
        parent::__construct();
        $this->menuFacade = $menuFacade;
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
        parent::beforeRender();
        $this->template->menus = $this->menuFacade->getOrderedVisibleMenu();
    }
}
