<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Unit\DependencyInjection\IfrostDoctrineApiAuthExtension;

use Ifrost\DoctrineApiAuthBundle\DependencyInjection\IfrostDoctrineApiAuthExtension;
use Ifrost\DoctrineApiAuthBundle\Entity\ApiUserInterface;
use Ifrost\DoctrineApiAuthBundle\Entity\TokenInterface;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\Token;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\User;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Sample;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class GetConfigurationTest extends TestCase
{
    public function testShouldLoadDefaultConfig()
    {
        // Given
        $configs = [
            'ifrost_doctrine_api_auth' => [
                'exception_listener' => true,
                'ttl' => 2592000,
                'token_parameter_name' => 'refresh_token',
            ],
        ];
        $containerBuilder = new ContainerBuilder();

        // When
        (new IfrostDoctrineApiAuthExtension())->load($configs, $containerBuilder);

        // Then
        $this->assertTrue($containerBuilder->getParameter('ifrost_doctrine_api_auth.exception_listener'));
        $this->assertFalse($containerBuilder->has('ifrost_doctrine_api_auth.token_entity'));
        $this->assertFalse($containerBuilder->has('ifrost_doctrine_api_auth.user_entity'));
        $this->assertEquals(2592000, $containerBuilder->getParameter('ifrost_doctrine_api_auth.ttl'));
        $this->assertEquals('refresh_token', $containerBuilder->getParameter('ifrost_doctrine_api_auth.token_parameter_name'));
        $this->assertFalse($containerBuilder->has('ifrost_doctrine_api_auth.cookie'));
    }

    public function testShouldLoadTokenEntityAndUserEntity()
    {
        // Given
        $configs = [
            'ifrost_doctrine_api_auth' => [
                'token_entity' => Token::class,
                'user_entity' => User::class,
            ],
        ];
        $containerBuilder = new ContainerBuilder();

        // When
        (new IfrostDoctrineApiAuthExtension())->load($configs, $containerBuilder);

        // Then
        $this->assertEquals(Token::class, $containerBuilder->getParameter('ifrost_doctrine_api_auth.token_entity'));
        $this->assertEquals(User::class, $containerBuilder->getParameter('ifrost_doctrine_api_auth.user_entity'));
    }

    public function testShouldThrowInvalidArgumentExceptionWhenInvalidTokenEntity()
    {
        $this->assertInvalidEntitySetting('token_entity', Sample::class, TokenInterface::class);
    }

    public function testShouldThrowInvalidArgumentExceptionWhenInvalidUserEntity()
    {
        $this->assertInvalidEntitySetting('user_entity', Sample::class, ApiUserInterface::class);
    }

    private function assertInvalidEntitySetting(string $settingName, string $entityClassName, string $interfaceClassName)
    {
        // Expect
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Setting %s (%s) has to implement "%s" interface.', $settingName, $entityClassName, $interfaceClassName));

        // Given
        $configs = [
            'ifrost_doctrine_api_auth' => [
                $settingName => Sample::class,
            ],
        ];
        $containerBuilder = new ContainerBuilder();

        // When & Then
        (new IfrostDoctrineApiAuthExtension())->load($configs, $containerBuilder);
    }
}
