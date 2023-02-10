<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Generator;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Ramsey\Uuid\Uuid;

class RefreshTokenGenerator implements RefreshTokenGeneratorInterface
{
    public function __construct(private JWSProviderInterface $jwsProvider)
    {
    }

    public function generate(): string
    {
        return $this->jwsProvider->create(
            [
                'uuid' => (string) Uuid::uuid4(),
                'token' => bin2hex(random_bytes(64) . Uuid::uuid4()),
            ]
        )->getToken();
    }
}
