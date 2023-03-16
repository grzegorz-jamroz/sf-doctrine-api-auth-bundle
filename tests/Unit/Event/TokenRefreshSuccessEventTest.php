<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Unit\Event;

use Ifrost\DoctrineApiAuthBundle\Entity\ApiUserInterface;
use Ifrost\DoctrineApiAuthBundle\Event\TokenRefreshSuccessEvent;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class TokenRefreshSuccessEventTest extends TestCase
{
    protected function setUp(): void
    {
        $this->user = new User(
            '3fc713ae-f1b8-43a6-95d2-e6d573fab41a',
            'tom@email.com'
        );
    }

    public function testShouldSetData()
    {
        // Given
        $data = [
            'current_data' => 'current data',
        ];
        $event = new TokenRefreshSuccessEvent($data, new JsonResponse(), $this->user);

        // When
        $event->setData([
            ...$event->getData(),
            'additional_data' => 'additional data',
        ]);

        // Then
        $this->assertEquals(
            [
                'current_data' => 'current data',
                'additional_data' => 'additional data',
            ],
            $event->getData()
        );
    }

    public function testShouldGetResponse()
    {
        // Given
        $response = new JsonResponse(['foo' => 'bar']);
        $event = new TokenRefreshSuccessEvent([], $response, $this->user);

        // When & Then
        $this->assertInstanceOf(JsonResponse::class, $event->getResponse());
        $this->assertEquals(
            ['foo' => 'bar'],
            json_decode($event->getResponse()->getContent(), true),
        );
    }

    public function testShouldGetUser()
    {
        // Given
        $event = new TokenRefreshSuccessEvent([], new JsonResponse(), $this->user);

        // When & Then
        $this->assertInstanceOf(ApiUserInterface::class, $event->getUser());
        $this->assertEquals(
            $this->user,
            $event->getUser(),
        );
    }
}
