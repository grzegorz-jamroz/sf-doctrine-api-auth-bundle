<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Unit;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
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
}
