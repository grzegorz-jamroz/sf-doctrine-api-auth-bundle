<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\EventSubscriber;

use Doctrine\DBAL\Connection;
use Ifrost\DoctrineApiBundle\Entity\EntityInterface;
use Ifrost\DoctrineApiBundle\Query\Entity\EntityQuery;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use PlainDataTransformer\Transform;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTAuthenticatedEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Connection $db,
        private string $tokenClassName,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::JWT_AUTHENTICATED => 'verifyToken',
        ];
    }

    public function verifyToken(JWTAuthenticatedEvent $event)
    {
        $user = $event->getToken()->getUser();

        if (!$user instanceof EntityInterface) {
            return;
        }

        try {
            $uuid = Transform::toString($event->getPayload()['uuid'] ?? '');
            $this->db->fetchOne(EntityQuery::class, $this->tokenClassName::getTableName(), $uuid);
        } catch (\Exception) {
            throw new InvalidTokenException('Invalid JWT Token');
        }
    }
}
