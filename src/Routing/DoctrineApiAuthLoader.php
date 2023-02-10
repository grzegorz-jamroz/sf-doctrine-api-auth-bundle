<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Routing;

use Ifrost\DoctrineApiAuthBundle\Action\LogoutAction;
use Ifrost\DoctrineApiAuthBundle\Action\RefreshTokenAction;
use Symfony\Bundle\FrameworkBundle\Routing\RouteLoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class DoctrineApiAuthLoader implements RouteLoaderInterface
{
    public function __invoke(): RouteCollection
    {
        $collection = new RouteCollection();

        $route = new Route(
            '/logout',
            [
                '_controller' => LogoutAction::class,
            ],
        );
        $route->setMethods([Request::METHOD_POST]);
        $collection->add('logout', $route);

        $route = new Route(
            '/token/refresh',
            [
                '_controller' => RefreshTokenAction::class,
            ],
        );
        $route->setMethods([Request::METHOD_POST]);
        $collection->add('token_refresh', $route);

        return $collection;
    }
}
