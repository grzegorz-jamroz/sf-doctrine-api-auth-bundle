<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\TokenExtractor;

interface TokenExtractorInterface
{
    public function extract(): string;
}
