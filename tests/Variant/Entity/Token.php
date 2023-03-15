<?php

namespace Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Ifrost\DoctrineApiAuthBundle\Entity\TokenInterface;
use PlainDataTransformer\Transform;

#[ORM\Entity(readOnly: true)]
class Token implements TokenInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', length: 36, unique: true)]
    private string $uuid;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_uuid', referencedColumnName: 'uuid', nullable: false)]
    private string $userUuid;

    #[ORM\Column]
    private int $iat;

    #[ORM\Column]
    private int $exp;

    #[ORM\Column(length: 255, nullable: true)]
    private string $device;

    #[ORM\Column(type: 'uuid', length: 36, unique: true)]
    private string $refreshTokenUuid;

    public function __construct(
        string $uuid,
        string $userUuid,
        int $iat,
        int $exp,
        string $device,
        string $refreshTokenUuid,
    ) {
        $this->uuid = $uuid;
        $this->userUuid = $userUuid;
        $this->iat = $iat;
        $this->exp = $exp;
        $this->device = $device;
        $this->refreshTokenUuid = $refreshTokenUuid;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
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

    public function getRefreshTokenUuid(): string
    {
        return $this->refreshTokenUuid;
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

    public static function createFromArray(array $data): static|self
    {
        return new self(
            Transform::toString($data['uuid'] ?? ''),
            Transform::toString($data['user_uuid'] ?? ''),
            Transform::toInt($data['iat'] ?? 0),
            Transform::toInt($data['exp'] ?? 0),
            Transform::toString($data['device'] ?? ''),
            Transform::toString($data['refresh_token_uuid'] ?? ''),
        );
    }

    /**
     * @return array<string, string>
     */
    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'user_uuid' => $this->userUuid,
            'iat' => $this->iat,
            'exp' => $this->exp,
            'device' => $this->device,
            'refresh_token_uuid' => $this->refreshTokenUuid,
        ];
    }

    public function getWritableFormat(): array
    {
        return $this->jsonSerialize();
    }
}
