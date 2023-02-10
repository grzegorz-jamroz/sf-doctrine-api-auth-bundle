<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Payload;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\MissingTokenException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWSProvider\JWSProviderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class JwtPayloadFactory implements TokenPayloadFactoryInterface
{
    private Request $request;

    public function __construct(
        readonly RequestStack $requestStack,
        readonly private TokenExtractorInterface $tokenExtractor,
        readonly private JWSProviderInterface $jwsProvider,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @throws JWTDecodeFailureException
     */
    public function create(): array
    {
        $token = $this->tokenExtractor->extract($this->request);

        if ($token === false) {
            throw new MissingTokenException('JWT Token not found');
        }

        return $this->decodeToken($token);
    }

    /**
     * @throws JWTDecodeFailureException
     */
    private function decodeToken(string $token): array
    {
        try {
            $jws = $this->jwsProvider->load($token);
        } catch (\Exception $e) {
            throw new JWTDecodeFailureException(JWTDecodeFailureException::INVALID_TOKEN, 'Invalid JWT Token', $e);
        }

        if ($jws->isInvalid()) {
            throw new JWTDecodeFailureException(JWTDecodeFailureException::INVALID_TOKEN, 'Invalid JWT Token', null, $jws->getPayload());
        }

        if ($jws->isExpired()) {
            return $jws->getPayload();
        }

        if (!$jws->isVerified()) {
            throw new JWTDecodeFailureException(JWTDecodeFailureException::UNVERIFIED_TOKEN, 'Unable to verify the given JWT through the given configuration. If the "lexik_jwt_authentication.encoder" encryption options have been changed since your last authentication, please renew the token. If the problem persists, verify that the configured keys/passphrase are valid.', null, $jws->getPayload());
        }

        return $jws->getPayload();
    }
}
