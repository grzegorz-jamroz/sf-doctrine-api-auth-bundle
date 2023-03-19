<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class TokenRefreshAfterGetUserDataEvent extends Event
{
    public function __construct(
        readonly private string $userClassName,
        private array $data,
    ) {
    }

    public function getUserClassName(): string
    {
        return $this->userClassName;
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
