<?php

declare(strict_types=1);

namespace App\Modules\Front;

use Nette;
use Nette\Application\Responses;
use Nette\Http;
use Tracy\ILogger;

final class ErrorPresenter implements Nette\Application\IPresenter {

    use Nette\SmartObject;

    private ILogger $logger;

    public function __construct(ILogger $logger) {
        $this->logger = $logger;
    }

    public function run(Nette\Application\Request $request): Nette\Application\Response {
        $exception = $request->getParameter('exception');

        if (!$exception instanceof \Throwable) {
            return new Responses\CallbackResponse(fn(Http\IRequest $httpRequest, Http\IResponse $httpResponse) => $httpResponse->setCode(Http\IResponse::S500_InternalServerError)
            );
        }

        if ($exception instanceof Nette\Application\BadRequestException) {
            [$module, , $sep] = Nette\Application\Helpers::splitName($request->getPresenterName());
            return new Responses\ForwardResponse($request->setPresenterName($module . $sep . 'Error4xx'));
        }

        $this->logger->log($exception, ILogger::EXCEPTION);

        return new Responses\CallbackResponse(function (Http\IRequest $httpRequest, Http\IResponse $httpResponse): void {
            $httpResponse->setCode(Http\IResponse::S500_InternalServerError);

            if (preg_match('#^text/html(?:;|$)#', (string)$httpResponse->getHeader('Content-Type'))) {
                require __DIR__ . '/templates/Error/500.phtml';
            }
        });
    }
}
