<?php

declare(strict_types=1);

namespace App\Modules\Front;

use App\Model\MenuFacade;
use Nette\Application\Attributes\Requires;
use Nette\Application\BadRequestException;
use Tracy\ILogger;

// Accept any HTTP method, otherwise e.g. an OPTIONS request fails here again with 405
#[Requires(methods: '*', forward: true)]
final class Error4xxPresenter extends BasePresenter {

    public function __construct(MenuFacade $menuFacade, ILogger $logger) {
        parent::__construct($menuFacade, $logger);
    }

    public function renderDefault(?BadRequestException $exception = null): void {
        $errorCode = $exception ? $exception->getCode() : 404;
        $templateFile = __DIR__ . "/templates/Error/{$errorCode}.latte";

        $this->template->setFile(is_file($templateFile) ? $templateFile : __DIR__ . '/templates/Error/4xx.latte');
    }
}
