<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Event;

use Ifrost\DoctrineApiAuthBundle\Entity\ApiUserInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\EventDispatcher\Event;

class TokenRefreshSuccessEvent extends Event
{
    public function __construct(
        private array $data,
        private readonly JsonResponse $response,
        private readonly ApiUserInterface $user
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @var array<int, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getResponse(): JsonResponse
    {
        return $this->response;
    }

    public function getUser(): ApiUserInterface
    {
        return $this->user;
    }
}
