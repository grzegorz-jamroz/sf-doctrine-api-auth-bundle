<?php

namespace Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ifrost\DoctrineApiAuthBundle\Entity\TokenInterface;
use PlainDataTransformer\Transform;
use Ramsey\Uuid\Doctrine\UuidV7Generator;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(readOnly: true)]
class Token implements TokenInterface
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid_binary", unique: true)]
    #[ORM\GeneratedValue(strategy: "CUSTOM")]
    #[ORM\CustomIdGenerator(class: UuidV7Generator::class)]
    private UuidInterface $uuid;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_uuid', referencedColumnName: 'uuid', nullable: false)]
    private UuidInterface $userUuid;

    #[ORM\Column(type: "uuid_binary", unique: true)]
    private UuidInterface $refreshTokenUuid;

    #[ORM\Column]
    private int $iat;

    #[ORM\Column]
    private int $exp;

    #[ORM\Column(length: 255, nullable: true)]
    private string $device;

    public function __construct(
        UuidInterface $uuid,
        UuidInterface $userUuid,
        UuidInterface $refreshTokenUuid,
        int $iat,
        int $exp,
        string $device,
    ) {
        $this->uuid = $uuid;
        $this->userUuid = $userUuid;
        $this->refreshTokenUuid = $refreshTokenUuid;
        $this->iat = $iat;
        $this->exp = $exp;
        $this->device = $device;
    }

    public function getUuid(): UuidInterface
    {
        return $this->uuid;
    }

    public function getUserUuid(): UuidInterface
    {
        return $this->userUuid;
    }

    public function getRefreshTokenUuid(): UuidInterface
    {
        return $this->refreshTokenUuid;
    }

    public function getIat(): int
    {
        return $this->iat;
    }

    public function getExp(): int
    {
        return $this->exp;
    }

    public function getDevice(): string
    {
        return $this->device;
    }

    public static function getTableName(): string
    {
        return 'token';
    }

    /**
     * @return array<int, string>
     */
    public static function getFields(): array
    {
        return array_keys(self::createFromArray([])->jsonSerialize());
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => (string) $this->uuid,
            'user_uuid' => (string) $this->userUuid,
            'refresh_token_uuid' => (string) $this->refreshTokenUuid,
            'iat' => $this->iat,
            'exp' => $this->exp,
            'device' => $this->device,
        ];
    }

    public function getWritableFormat(): array
    {
        return [
            ...$this->jsonSerialize(),
            'uuid' => $this->uuid->getBytes(),
            'user_uuid' => $this->userUuid->getBytes(),
            'refresh_token_uuid' => $this->refreshTokenUuid->getBytes(),
        ];
    }

    public static function createFromArray(array $data): static|self
    {
        return new self(
            $data['uuid'] ?? Uuid::uuid7(),
            $data['user_uuid'] ?? Uuid::uuid7(),
            $data['refresh_token_uuid'] ?? Uuid::uuid7(),
            Transform::toInt($data['iat'] ?? 0),
            Transform::toInt($data['exp'] ?? 0),
            Transform::toString($data['device'] ?? ''),
        );
    }

    public static function createFromRequest(array $data): static|self
    {
        return new self(
            isset($data['uuid']) ? Uuid::fromString($data['uuid']) : Uuid::uuid7(),
            isset($data['user_uuid']) ? Uuid::fromString($data['user_uuid']) : Uuid::uuid7(),
            isset($data['refresh_token_uuid']) ? Uuid::fromString($data['refresh_token_uuid']) : Uuid::uuid7(),
            Transform::toInt($data['iat'] ?? 0),
            Transform::toInt($data['exp'] ?? 0),
            Transform::toString($data['device'] ?? ''),
        );
    }
}
