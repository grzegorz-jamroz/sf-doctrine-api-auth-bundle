<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\EventSubscriber;

use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTCreatedEventSubscriber implements EventSubscriberInterface
{
    public function __construct(private int $ttl)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_CREATED => 'extendPayload',
        ];
    }

    public function extendPayload(JWTCreatedEvent $event)
    {
        $now = time();
        $data = [
            'uuid' => (string) Uuid::uuid4(),
            'iat' => $now,
            'exp' => $now + $this->ttl,
            'device' => null,
        ];
        $event->setData([
            ...$event->getData(),
            ...$data,
        ]);
    }
}
