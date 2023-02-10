<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Generator;

interface RefreshTokenGeneratorInterface
{
    public function generate(): string;
}
