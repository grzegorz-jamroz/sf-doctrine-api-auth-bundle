<?php

declare(strict_types=1);

namespace EventSubscriber;

use Ifrost\DoctrineApiAuthBundle\Event\TokenRefreshAfterGetUserDataEvent;
use Ifrost\DoctrineApiAuthBundle\Events;
use Ifrost\DoctrineApiAuthBundle\EventSubscriber\TokenRefreshAfterGetUserDataSubscriber;
use Ifrost\DoctrineApiAuthBundle\Tests\Unit\BundleTestCase;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\User;

class TokenRefreshAfterGetUserDataSubscriberTest extends BundleTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->eventSubscriber = new TokenRefreshAfterGetUserDataSubscriber();
        $this->event = new TokenRefreshAfterGetUserDataEvent(User::class, [
            ...$this->user->jsonSerialize(),
            'roles' => json_encode($this->user->getRoles()),
        ]);
    }

    public function testShouldGetSubscribedEvents()
    {
        // When & Then
        $this->assertEquals(
            [
                Events::TOKEN_REFRESH_AFTER_GET_USER_DATA => 'handle',
            ],
            $this->eventSubscriber->getSubscribedEvents()
        );
    }

    public function testShouldDecodeUserRoles()
    {
        // When
        $this->eventSubscriber->handle($this->event);

        // Then
        $this->assertEquals(
            $this->user->jsonSerialize(),
            $this->event->getData(),
        );
    }
}
