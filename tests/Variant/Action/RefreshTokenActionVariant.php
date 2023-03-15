<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Variant\Action;

use Ifrost\DoctrineApiAuthBundle\Action\RefreshTokenAction;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\Token;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\User;
use Ifrost\Foundations\ArrayConstructable;
use PlainDataTransformer\Transform;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RefreshTokenActionVariant extends RefreshTokenAction implements ArrayConstructable
{
    public static function createFromArray(array $data = []): self
    {
        $requestStack = new RequestStack();
        $requestStack->push($data['request'] ?? new Request());

        return new self(
            Transform::toInt($data['ttl'] ?? 3600),
            Transform::toString($data['tokenParameterName'] ?? 'refresh_token'),
            Transform::toString($data['tokenClassName'] ?? Token::class),
            Transform::toString($data['userClassName'] ?? User::class),
            Transform::toBool($data['validateJwt'] ?? false),
            Transform::toBool($data['returnUserInBody'] ?? false),
            Transform::toBool($data['returnRefreshTokenInBody'] ?? false),
            Transform::toArray($data['cookieSettings'] ?? []),
            $data['jwtPayloadFactory'],
            $data['refreshTokenPayloadFactory'],
            $data['refreshTokenEncoder'],
            $data['db'],
            $data['dispatcher'],
            $data['jwtManager'],
            $data['refreshTokenGenerator'],
        );
    }
}
