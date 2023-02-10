<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Payload;

interface TokenPayloadFactoryInterface
{
    public function create(): array;
}
