<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Generator;

use Ramsey\Uuid\Uuid;

class RefreshTokenGenerator
{
    public function generate(): string
    {
        return bin2hex(random_bytes(64) . Uuid::uuid4());
    }
}
