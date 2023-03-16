<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Unit;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Ifrost\DoctrineApiAuthBundle\Entity\ApiUserInterface;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\Token;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\User;
use Ifrost\DoctrineApiBundle\Exception\NotFoundException;
use Ifrost\DoctrineApiBundle\Query\Entity\EntityQuery;
use Ifrost\DoctrineApiBundle\Utility\DbClient;
use PHPUnit\Framework\TestCase;
use PlainDataTransformer\Transform;

class BundleTestCase extends TestCase
{
    protected Connection $dbal;
    protected DbClient $db;
    protected User $user;
    protected Token $token;

    protected function setUp(): void
    {
        $this->dbal = DriverManager::getConnection([
            'url' => Transform::toString($_ENV['DATABASE_URL'] ?? ''),
        ]);
        $this->db = new DbClient($this->dbal);
        $this->user = User::createFromArray([
            'uuid' => '3fc713ae-f1b8-43a6-95d2-e6d573fab41a',
            'email' => 'tom.smith@email.com',
            'roles' => ['ROLE_USER'],
            'password' => '123'
        ]);
        $this->token = Token::createFromArray([
            'uuid' => '25feb06b-0c1e-4416-86fc-706134f2c7de',
            'user_uuid' => '3fc713ae-f1b8-43a6-95d2-e6d573fab41a',
            'iat' => 1573457094,
            'exp' => 1573460694,
            'device' => '',
            'refresh_token_uuid' => '60efd5f1-d831-4c02-863d-4ee11843fc2e',
        ]);
    }

    protected function truncateTable(string $tableName): void
    {
        $this->dbal->executeStatement("TRUNCATE TABLE $tableName");
    }

    protected function createUserIfNotExists(User $user): void
    {
        try {
            $this->db->fetchColumn(EntityQuery::class, $user::getTableName(), $user->getUuid());
        } catch (Exception $e) {
            throw $e;
        } catch (NotFoundException) {
            $data = $user->jsonSerialize();
            $data['roles'] = json_encode($data['roles']);
            $data['password'] = $user->getPassword();
            $this->db->insert($user::getTableName(), $data);
        }
    }
}
