<?php

declare(strict_types=1);

namespace App\Modules\Front;

use App\Model\MenuFacade;
use Tracy\ILogger;

final class HomepagePresenter extends BasePresenter {

    public function __construct(MenuFacade $menuFacade, ILogger $logger) {
        parent::__construct($menuFacade, $logger);
    }

    public function renderDefault(): void {
    }
}
