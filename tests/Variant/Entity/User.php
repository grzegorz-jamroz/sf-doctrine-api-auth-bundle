<?php

namespace Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ifrost\DoctrineApiAuthBundle\Entity\ApiUserInterface;
use PlainDataTransformer\Transform;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(readOnly: true)]
class User implements ApiUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid_binary", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private UuidInterface $uuid;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private string $password;

    /**
     * @var array<int, string>
     */
    #[ORM\Column]
    private array $roles;

    public function __construct(
        UuidInterface $uuid,
        string $email,
        string $password = '',
        array $roles = [],
    ) {
        $this->uuid = $uuid;
        $this->email = $email;
        $this->password = $password;
        $this->roles = $roles;
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUsername(): string
    {
        return $this->getEmail();
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public static function getTableName(): string
    {
        return 'user';
    }

    /**
     * @return array<int, string>
     */
    public static function getFields(): array
    {
        return [
            ...array_keys(self::createFromArray([])->jsonSerialize()),
            'password',
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => (string) $this->uuid,
            'email' => $this->email,
            'roles' => $this->getRoles(),
        ];
    }

    public function getWritableFormat(): array
    {
        return [
            ...$this->jsonSerialize(),
            'uuid' => $this->uuid->getBytes(),
            'password' => $this->password,
            'roles' => json_encode($this->getRoles()),
        ];
    }

    public static function createFromArray(array $data): static|self
    {
        return new self(
            $data['uuid'] ?? Uuid::uuid7(),
            Transform::toString($data['email'] ?? ''),
            Transform::toString($data['password'] ?? ''),
            Transform::toArray($data['roles'] ?? []),
        );
    }

    public static function createFromRequest(array $data): static|self
    {
        return new self(
            isset($data['uuid']) ? Uuid::fromString($data['uuid']) : Uuid::uuid7(),
            Transform::toString($data['email'] ?? ''),
            Transform::toString($data['password'] ?? ''),
            Transform::toArray($data['roles'] ?? []),
        );
    }
}
