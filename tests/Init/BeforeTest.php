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
            CREATE TABLE IF NOT EXISTS user (
                uuid CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',
                email VARCHAR(180) NOT NULL,
                password VARCHAR(255) NOT NULL,
                roles LONGTEXT NOT NULL COMMENT '(DC2Type:json)',
                UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
                PRIMARY KEY(uuid)
            ) ENGINE = InnoDB DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`
        SQL;
        $controller->getDbal()->executeStatement($sql);
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS token (
                uuid CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)', 
                user_uuid CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)', 
                iat INT NOT NULL, 
                exp INT NOT NULL, 
                device VARCHAR(255) DEFAULT NULL, 
                refresh_token_uuid CHAR(36) NOT NULL COMMENT '(DC2Type:uuid)',                
                UNIQUE INDEX UNIQ_5F37A13B724FCBF2 (refresh_token_uuid),
                INDEX IDX_5F37A13BABFE1C6F (user_uuid),
                PRIMARY KEY(uuid)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        SQL;
        $controller->getDbal()->executeStatement($sql);
    }
}

