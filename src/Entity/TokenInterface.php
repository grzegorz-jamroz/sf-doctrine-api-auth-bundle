<?php

namespace Ifrost\DoctrineApiAuthBundle\Entity;

use Ifrost\DoctrineApiBundle\Entity\EntityInterface;
use Ramsey\Uuid\UuidInterface;

interface TokenInterface extends EntityInterface
{
    public function getUuid(): UuidInterface;

    public function getUserUuid(): UuidInterface;

    public function getRefreshTokenUuid(): UuidInterface;

    public function getIat(): int;

    public function getExp(): int;

    public function getDevice(): string;
}
