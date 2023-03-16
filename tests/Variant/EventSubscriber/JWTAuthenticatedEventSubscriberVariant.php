<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Variant\EventSubscriber;

use Ifrost\DoctrineApiAuthBundle\EventSubscriber\JWTAuthenticatedEventSubscriber;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\Token;
use Ifrost\Foundations\ArrayConstructable;
use PlainDataTransformer\Transform;

class JWTAuthenticatedEventSubscriberVariant extends JWTAuthenticatedEventSubscriber implements ArrayConstructable
{
    public static function createFromArray(array $data = []): self
    {
        return new self(
            Transform::toString($data['tokenClassName'] ?? Token::class),
            $data['db'],
        );
    }
}
