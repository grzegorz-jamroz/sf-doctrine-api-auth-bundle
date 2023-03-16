<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Unit\EventSubscriber;

use Ifrost\DoctrineApiAuthBundle\EventSubscriber\JWTCreatedEventSubscriber;
use Ifrost\DoctrineApiAuthBundle\Tests\Unit\BundleTestCase;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;

class JWTCreatedEventSubscriberTest extends BundleTestCase
{
    private array $data;
    private JWTCreatedEvent $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventSubscriber = new JWTCreatedEventSubscriber(3600);
        $this->data = [
            'roles' => $this->user->getRoles(),
            'username' => $this->user->getEmail(),
        ];
        $this->event = new JWTCreatedEvent($this->data, $this->user);
    }

    public function testShouldGetSubscribedEvents()
    {
        // When & Then
        $this->assertEquals(
            [
                Events::JWT_CREATED => 'extendPayload',
            ],
            $this->eventSubscriber->getSubscribedEvents()
        );
    }

    public function testShouldEnrichEventData()
    {
        // Expect
        $this->assertCount(2, $this->event->getData());

        // When
        $this->eventSubscriber->extendPayload($this->event);

        // Then
        $this->assertCount(6, $this->event->getData());
        $this->assertArrayHasKey('roles', $this->event->getData());
        $this->assertArrayHasKey('username', $this->event->getData());
        $this->assertArrayHasKey('uuid', $this->event->getData());
        $this->assertArrayHasKey('iat', $this->event->getData());
        $this->assertArrayHasKey('exp', $this->event->getData());
        $this->assertArrayHasKey('device', $this->event->getData());
    }
}
