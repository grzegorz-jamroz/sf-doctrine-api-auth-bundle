<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Init;

use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Controller\DoctrineApiControllerVariant;
use PHPUnit\Framework\TestCase;

class BeforeTest extends TestCase
{
    public function testShouldSetupEnvironmentBeforeAllTests()
    {
        $this->createTableIfNotExists();
        $this->assertEquals(1, 1);
    }

    protected function createTableIfNotExists(): void
    {
        $controller = new DoctrineApiControllerVariant();
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS `user` (
                `uuid` BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary)',
                `email` VARCHAR(180) NOT NULL,
                `password` VARCHAR(255) NOT NULL,
                `roles` JSON NOT NULL COMMENT '(DC2Type:json)',
                UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
                PRIMARY KEY(uuid)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL;
        $controller->getDbal()->executeStatement($sql);
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS `token` (
                `uuid` BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary)',
                `user_uuid` BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary)',
                `refresh_token_uuid` BINARY(16) NOT NULL COMMENT '(DC2Type:uuid_binary)',
                `iat` INT NOT NULL,
                `exp` INT NOT NULL,
                `device` VARCHAR(255) DEFAULT NULL,
                UNIQUE INDEX UNIQ_5F37A13B724FCBF2 (refresh_token_uuid),
                INDEX IDX_5F37A13BABFE1C6F (user_uuid),
                PRIMARY KEY(uuid)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB        
        SQL;
        $controller->getDbal()->executeStatement($sql);
    }
}

