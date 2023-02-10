<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Payload;

use Ifrost\DoctrineApiAuthBundle\TokenExtractor\TokenExtractorInterface as RefreshTokenExtractorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;

class RefreshTokenPayloadFactory implements TokenPayloadFactoryInterface
{
    public function __construct(
        readonly private RefreshTokenExtractorInterface $refreshTokenExtractor,
        readonly private JWTEncoderInterface $refreshTokenEncoder,
    ) {
    }

    /**
     * @throws JWTDecodeFailureException
     */
    public function create(): array
    {
        $refreshToken = $this->refreshTokenExtractor->extract();

        try {
            return $this->refreshTokenEncoder->decode($refreshToken);
        } catch (JWTDecodeFailureException $e) {
            $message = str_replace(['JWT Token', 'JWT'], 'Refresh Token', $e->getMessage());

            throw new JWTDecodeFailureException($e->getReason(), $message, null, $e->getPayload());
        }
    }
}
