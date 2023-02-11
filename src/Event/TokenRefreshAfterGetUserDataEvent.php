<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class TokenRefreshAfterGetUserDataEvent extends Event
{
    public function __construct(
        private string $entityClassName,
        private array $data,
    ) {
    }

    public function getEntityClassName(): string
    {
        return $this->entityClassName;
    }

    /**
     * @return array<string, string|int|bool|float|null>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @var array<int, string|int|bool|float|null> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
