<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\TokenExtractor;

use Ifrost\ApiBundle\Utility\ApiRequest;
use PlainDataTransformer\Transform;

class RefreshTokenExtractor implements TokenExtractorInterface
{
    public function __construct(
        private string $name,
        private ApiRequest $apiRequest,
    )
    {
    }

    public function extract(): string
    {
        return Transform::toString($this->apiRequest->getRequiredField($this->name));
    }
}
