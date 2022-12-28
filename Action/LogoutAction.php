<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Action;

use Ifrost\DoctrineApiBundle\Utility\DbClient;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use PlainDataTransformer\Transform;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class LogoutAction
{
    private Request $request;

    public function __construct(
        RequestStack $requestStack,
        private DbClient $db,
        private TokenExtractorInterface $tokenExtractor,
        private JWTTokenManagerInterface $jwtManager,
        private string $tokenClassName,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function __invoke(): Response
    {
        $token = $this->tokenExtractor->extract($this->request);
        $payload = $this->jwtManager->parse($token);
        $uuid = Transform::toString($payload['uuid'] ?? '');

        $this->db->delete(
            $this->tokenClassName::getTableName(),
            [
                'uuid' => $uuid,
            ]
        );

        return new JsonResponse();
    }
}
