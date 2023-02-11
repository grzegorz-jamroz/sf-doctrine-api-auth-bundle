<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\EventSubscriber;

use Ifrost\DoctrineApiAuthBundle\Event\TokenRefreshAfterGetUserDataEvent;
use Ifrost\DoctrineApiAuthBundle\Events;
use PlainDataTransformer\Transform;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TokenRefreshAfterGetUserDataSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            Events::TOKEN_REFRESH_AFTER_GET_USER_DATA => 'handle',
        ];
    }

    public function handle(TokenRefreshAfterGetUserDataEvent $event): void
    {
        $userData = $event->getData();
        $roles = Transform::toString($userData['roles'] ?? '[]');
        $userData['roles'] = json_decode($roles, true);
        $event->setData($userData);
    }
}
