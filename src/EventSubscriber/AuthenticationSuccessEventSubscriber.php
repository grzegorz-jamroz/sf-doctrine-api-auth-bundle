<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\EventSubscriber;

use Doctrine\DBAL\Connection;
use Ifrost\DoctrineApiAuthBundle\Generator\RefreshTokenGeneratorInterface;
use Ifrost\DoctrineApiBundle\Entity\EntityInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class AuthenticationSuccessEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private int $ttl,
        private string $tokenParameterName,
        private string $tokenClassName,
        private bool $returnUserInBody,
        private bool $returnRefreshTokenInBody,
        private array $cookieSettings,
        private JWTTokenManagerInterface $jwtManager,
        private Connection $dbal,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private JWTEncoderInterface $refreshTokenEncoder,
    ) {
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
        $refreshToken = $this->refreshTokenGenerator->generate();
        $refreshTokenPayload = $this->refreshTokenEncoder->decode($refreshToken);
        $this->dbal->insert(
            $this->tokenClassName::getTableName(),
            [
                'uuid' => $payload['uuid'],
                'iat' => $payload['iat'],
                'exp' => $payload['exp'],
                'device' => $payload['device'],
                'user_uuid' => $user->getUuid(),
                'refresh_token_uuid' => $refreshTokenPayload['uuid'],
            ]
        );

        if ($this->returnUserInBody === true) {
            $data['user'] = $user;
        }

        if ($this->returnRefreshTokenInBody === true) {
            $data[$this->tokenParameterName] = $refreshToken;
        }

        $event->setData($data);
        $this->setCookie($refreshToken, $event->getResponse());
    }

    private function setCookie(string $refreshToken, Response $response): void
    {
        if ($this->cookieSettings['enabled']) {
            $response->headers->setCookie(
                new Cookie(
                    $this->tokenParameterName,
                    $refreshToken,
                    time() + $this->ttl,
                    $this->cookieSettings['path'],
                    $this->cookieSettings['domain'],
                    $this->cookieSettings['secure'],
                    $this->cookieSettings['http_only'],
                    false,
                    $this->cookieSettings['same_site']
                )
            );
        }
    }
}
