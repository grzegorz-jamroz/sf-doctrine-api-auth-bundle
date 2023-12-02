<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Unit\EventListener;

use Ifrost\DoctrineApiAuthBundle\EventListener\ExceptionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class ExceptionListenerTest extends TestCase
{
    private HttpKernelInterface $kernel;
    private Request $request;

    protected function setUp(): void
    {
        $this->kernel = $this->createMock(HttpKernelInterface::class);
        $this->request = new Request();
    }

    public function testShouldReturnResponseWithoutDetailedErrorMessageWhenProdEnvironment()
    {
        // Given
        $_ENV['APP_ENV'] = 'prod';
        $event = new ExceptionEvent(
            $this->kernel,
            $this->request,
            HttpKernelInterface::MAIN_REQUEST,
            new \Exception('Test error')
        );

        // When
        (new ExceptionListener())->onKernelException($event);

        // Then
        $this->assertEquals(
            ['message' => 'Oops! An Error Occurred'],
            json_decode($event->getResponse()->getContent(), true)
        );
        $this->assertEquals(400, $event->getResponse()->getStatusCode());
    }

    public function testShouldReturnResponseWithDetailedErrorMessageWhenDevEnvironment()
    {
        // Given
        $_ENV['APP_ENV'] = 'dev';
        $event = new ExceptionEvent(
            $this->kernel,
            $this->request,
            HttpKernelInterface::MAIN_REQUEST,
            new \Exception('Test error', 401)
        );

        // When
        (new ExceptionListener())->onKernelException($event);

        // Then
        $this->assertEquals(
            ['message' => 'Test error'],
            json_decode($event->getResponse()->getContent(), true)
        );
        $this->assertEquals(401, $event->getResponse()->getStatusCode());
    }

    public function testShouldReturnResponseWithCode403WhenPreviousExceptionIsInstanceOfInsufficientAuthenticationException()
    {
        // Given
        $_ENV['APP_ENV'] = 'dev';
        $event = new ExceptionEvent(
            $this->kernel,
            $this->request,
            HttpKernelInterface::MAIN_REQUEST,
            new \Exception('Test error', 401, new InsufficientAuthenticationException('Test error'))
        );

        // When
        (new ExceptionListener())->onKernelException($event);

        // Then
        $this->assertEquals(
            ['message' => 'Test error'],
            json_decode($event->getResponse()->getContent(), true)
        );
        $this->assertEquals(403, $event->getResponse()->getStatusCode());
    }
}
