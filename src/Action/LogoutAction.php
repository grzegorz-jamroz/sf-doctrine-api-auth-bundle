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
        private string $tokenParameterName,
        private array $cookieSettings,
        RequestStack $requestStack,
        private DbClient $db,
        private TokenExtractorInterface $tokenExtractor,
        private JWTTokenManagerInterface $jwtManager,
        private string $tokenClassName,
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->cookieSettings = array_merge([
            'enabled' => false,
            'same_site' => 'lax',
            'path' => '/',
            'domain' => null,
            'http_only' => true,
            'secure' => true,
        ], $cookieSettings);
    }

    public function __invoke(): Response
    {
        $response = new JsonResponse();
        $token = $this->tokenExtractor->extract($this->request);
        $payload = $this->jwtManager->parse($token);
        $uuid = Transform::toString($payload['uuid'] ?? '');
        $this->db->delete(
            $this->tokenClassName::getTableName(),
            [
                'uuid' => $uuid,
            ]
        );
        $this->clearCookie($response);

        return $response;
    }

    private function clearCookie(JsonResponse $response): void
    {
        if ($this->cookieSettings['enabled']) {
            $response->headers->clearCookie(
                $this->tokenParameterName,
                $this->cookieSettings['path'],
                $this->cookieSettings['domain'],
                $this->cookieSettings['secure'],
                $this->cookieSettings['http_only'],
                $this->cookieSettings['same_site']
            );
        }
    }
}
