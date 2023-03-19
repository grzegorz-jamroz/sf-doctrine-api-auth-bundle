<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Routing;

use Ifrost\DoctrineApiAuthBundle\Action\LogoutAction;
use Ifrost\DoctrineApiAuthBundle\Action\RefreshTokenAction;
use Symfony\Bundle\FrameworkBundle\Routing\RouteLoaderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class DoctrineApiAuthLoader implements RouteLoaderInterface
{
    private RouteCollection $collection;

    public function __construct(readonly private array $routes)
    {
        $this->collection = new RouteCollection();
    }

    public function __invoke(): RouteCollection
    {
        $this->addRoute([...$this->routes['logout'], 'action' => LogoutAction::class]);
        $this->addRoute([...$this->routes['refresh_token'], 'action' => RefreshTokenAction::class]);

        return $this->collection;
    }

    private function addRoute(array $routeConfig): void
    {
        $route = new Route(
            $routeConfig['path'],
            [
                '_controller' => $routeConfig['action'],
            ],
        );
        $route->setMethods($routeConfig['methods']);
        $this->collection->add($routeConfig['name'], $route);
    }
}
