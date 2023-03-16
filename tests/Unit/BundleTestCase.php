<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Unit;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Ifrost\DoctrineApiAuthBundle\Entity\ApiUserInterface;
use Ifrost\DoctrineApiBundle\Exception\NotFoundException;
use Ifrost\DoctrineApiBundle\Query\Entity\EntityQuery;
use Ifrost\DoctrineApiBundle\Utility\DbClient;
use PHPUnit\Framework\TestCase;
use PlainDataTransformer\Transform;

class BundleTestCase extends TestCase
{
    protected Connection $dbal;
    protected DbClient $db;

    protected function setUp(): void
    {
        $this->dbal = DriverManager::getConnection([
            'url' => Transform::toString($_ENV['DATABASE_URL'] ?? ''),
        ]);
        $this->db = new DbClient($this->dbal);
    }

    protected function truncateTable(string $tableName): void
    {
        $this->dbal->executeStatement("TRUNCATE TABLE $tableName");
    }

    protected function createUserIfNotExists(ApiUserInterface $user): void
    {
        try {
            $this->db->fetchColumn(EntityQuery::class, $user::getTableName(), $user->getUuid());
        } catch (Exception $e) {
            throw $e;
        } catch (NotFoundException) {
            $data = $user->jsonSerialize();
            $data['roles'] = json_encode($data['roles']);
            $this->db->insert($user::getTableName(), $data);
        }
    }
}
