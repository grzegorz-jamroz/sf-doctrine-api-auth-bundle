<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\EventSubscriber;

use Ifrost\DoctrineApiBundle\Query\Entity\EntityQuery;
use Ifrost\DoctrineApiBundle\Utility\DbClient;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTAuthenticatedEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidTokenException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTAuthenticatedEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private string $tokenClassName,
        private DbClient $db,
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
        try {
            $uuid = Uuid::fromString($event->getPayload()['uuid'] ?? '');
            $this->db->fetchOne(EntityQuery::class, $this->tokenClassName::getTableName(), $uuid->getBytes());
        } catch (\Exception) {
            throw new InvalidTokenException('Invalid JWT Token');
        }
    }
}
