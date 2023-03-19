<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Unit\Routing;

use Ifrost\DoctrineApiAuthBundle\Action\LogoutAction;
use Ifrost\DoctrineApiAuthBundle\Action\RefreshTokenAction;
use Ifrost\DoctrineApiAuthBundle\DependencyInjection\Configuration;
use Ifrost\DoctrineApiAuthBundle\Routing\DoctrineApiAuthLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpFoundation\Request;

class DoctrineApiAuthLoaderTest extends TestCase
{
    public function testShouldReturnRouteCollectionWithLoginAndRefreshTokenFromDefaultConfig()
    {
        // Given
        $configs = (new Processor())->processConfiguration(new Configuration(), []);

        // When
        $collection = (new DoctrineApiAuthLoader($configs['routes']))->__invoke();
        $logout = $collection->get('logout');
        $refreshToken = $collection->get('refresh_token');

        // Then
        $this->assertEquals('/logout', $logout->getPath());
        $this->assertEquals([Request::METHOD_POST], $logout->getMethods());
        $this->assertEquals(LogoutAction::class, $logout->getDefaults()['_controller']);
        $this->assertEquals('/token/refresh', $refreshToken->getPath());
        $this->assertEquals([Request::METHOD_POST], $refreshToken->getMethods());
        $this->assertEquals(RefreshTokenAction::class, $refreshToken->getDefaults()['_controller']);
    }
}
