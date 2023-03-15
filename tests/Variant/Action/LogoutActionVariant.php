<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Variant\Action;

use Ifrost\DoctrineApiAuthBundle\Action\LogoutAction;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\Token;
use Ifrost\Foundations\ArrayConstructable;
use PlainDataTransformer\Transform;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class LogoutActionVariant extends LogoutAction implements ArrayConstructable
{
    public static function createFromArray(array $data = []): static|self
    {
        $requestStack = new RequestStack();
        $requestStack->push($data['request'] ?? new Request());

        return new self(
            Transform::toString($data['tokenParameterName'] ?? 'refresh_token'),
            Transform::toArray($data['cookieSettings'] ?? []),
            $data['db'],
            Transform::toString($data['tokenClassName'] ?? Token::class),
            $data['jwtPayloadFactory'],
        );
    }
}
