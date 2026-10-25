<?php

declare(strict_types=1);

namespace App\Router;

use Nette;
use Nette\Application\Routers\RouteList;
use Nette\Routing\Router;

final class RouterFactory {
    public static function createRouter(): Router {
        $router = new RouteList();

        $router[] = $admin = new RouteList('Admin');
        $admin->addRoute('administrace/<presenter>/<action>', 'Login:default');

        $router[] = $front = new RouteList('Front');
        $front->addRoute('<presenter>/<action>', 'Homepage:default');

        return $router;
    }
}
