<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Variant\EventSubscriber;

use Ifrost\DoctrineApiAuthBundle\EventSubscriber\AuthenticationSuccessEventSubscriber;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\Token;
use Ifrost\Foundations\ArrayConstructable;
use PlainDataTransformer\Transform;

class AuthenticationSuccessEventSubscriberVariant extends AuthenticationSuccessEventSubscriber implements ArrayConstructable
{
    public static function createFromArray(array $data = []): self
    {
        $defaultCookieSettings = [
            'enabled' => false,
            'same_site' => 'lax',
            'path' => '/',
            'domain' => null,
            'http_only' => true,
            'secure' => true,
        ];

        return new self(
            Transform::toInt($data['ttl'] ?? 2592000),
            Transform::toString($data['tokenParameterName'] ?? 'refreshToken'),
            Transform::toString($data['tokenClassName'] ?? Token::class),
            Transform::toBool($data['returnUserInBody'] ?? false),
            Transform::toBool($data['returnRefreshTokenInBody'] ?? false),
            Transform::toArray($data['cookieSettings'] ?? $defaultCookieSettings),
            $data['jwtManager'],
            $data['dbal'],
            $data['refreshTokenGenerator'],
            $data['refreshTokenEncoder'],
        );
    }
}
