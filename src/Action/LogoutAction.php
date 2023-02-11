<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Action;

use Ifrost\DoctrineApiAuthBundle\Payload\JwtPayloadFactory;
use Ifrost\DoctrineApiBundle\Utility\DbClient;
use PlainDataTransformer\Transform;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class LogoutAction
{
    public function __construct(
        private string $tokenParameterName,
        private array $cookieSettings,
        private DbClient $db,
        private string $tokenClassName,
        readonly private JwtPayloadFactory $jwtPayloadFactory,
    ) {
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
        $payload = $this->jwtPayloadFactory->create();
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
