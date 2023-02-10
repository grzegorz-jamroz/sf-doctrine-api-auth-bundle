<?php

namespace Ifrost\DoctrineApiAuthBundle\Entity;

use Ifrost\DoctrineApiBundle\Entity\EntityInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface ApiUserInterface extends EntityInterface, UserInterface, PasswordAuthenticatedUserInterface
{
}
