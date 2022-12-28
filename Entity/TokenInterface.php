<?php

namespace Ifrost\DoctrineApiAuthBundle\Entity;

use Ifrost\DoctrineApiBundle\Entity\EntityInterface;

interface TokenInterface extends EntityInterface
{
    public function getUuid(): string;

    public function getUserUuid(): string;

    public function getIat(): int;

    public function getExp(): int;

    public function getDevice(): string;

    public function getRefreshToken(): string;
}
