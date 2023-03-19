<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Variant;

use Ifrost\DoctrineApiAuthBundle\Event\TokenRefreshAfterGetUserDataEvent;
use Ifrost\DoctrineApiAuthBundle\Event\TokenRefreshSuccessEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventDispatcherForRefreshTokenAction implements EventDispatcherInterface
{
    public function __construct(
        readonly private array $tokenRefreshAfterGetUserDataEventData = [],
        readonly private array $tokenRefreshSuccessEventData = [],
    ) {
    }

    public function dispatch(object $event, string $eventName = null): object
    {
        return match (true) {
            $event instanceof TokenRefreshAfterGetUserDataEvent => $this->dispatchTokenRefreshAfterGetUserDataEvent($event),
            $event instanceof TokenRefreshSuccessEvent => $this->dispatchTokenRefreshSuccessEvent($event),
            default => throw new \Exception('Unable to dispatch event - Invalid event.'),
        };
    }

    private function dispatchTokenRefreshAfterGetUserDataEvent(TokenRefreshAfterGetUserDataEvent $event): TokenRefreshAfterGetUserDataEvent
    {
        $event->setData([
            ...$event->getData(),
            ...$this->tokenRefreshAfterGetUserDataEventData,
        ]);

        return $event;
    }

    private function dispatchTokenRefreshSuccessEvent(TokenRefreshSuccessEvent $event): TokenRefreshSuccessEvent
    {
        $event->setData([
            ...$event->getData(),
            ...$this->tokenRefreshSuccessEventData,
        ]);

        return $event;
    }
}
