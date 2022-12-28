<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\EventSubscriber;

use Doctrine\DBAL\Connection;
use Ifrost\DoctrineApiAuthBundle\Generator\RefreshTokenGenerator;
use Ifrost\DoctrineApiBundle\Entity\EntityInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;

class AuthenticationSuccessEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private Connection $db,
        private string $tokenClassName,
    )
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'handle',
        ];
    }

    public function handle(AuthenticationSuccessEvent $event)
    {
        $user = $event->getUser();

        if (!$user instanceof EntityInterface) {
            return;
        }

        $data = $event->getData();
        $payload = $this->jwtManager->parse($data['token'] ?? '');
        $refreshToken = (new RefreshTokenGenerator())->generate();
        $this->db->insert(
            $this->tokenClassName::getTableName(),
            [
            'uuid' => $payload['uuid'],
            'iat' => $payload['iat'],
            'exp' => $payload['exp'],
            'device' => $payload['device'],
            'user_uuid' => $user->getUuid(),
            'refresh_token' => $refreshToken,
            ]
        );
        $event->setData([
            ...$event->getData(),
            'refresh_token' => $refreshToken,
        ]);
        $event->getResponse()->headers->setCookie(
            new Cookie(
                'refresh_token',
                $refreshToken,
                time() + 31536000,
                '/',
                null,
                true,
                true,
                false,
                Cookie::SAMESITE_LAX
            )
        );
    }
}
