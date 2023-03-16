<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Unit\Query\FindTokenByRefreshTokenUuidQuery;

use Doctrine\DBAL\DriverManager;
use Ifrost\DoctrineApiAuthBundle\Query\FindTokenByRefreshTokenUuidQuery;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Entity\Token;
use PHPUnit\Framework\TestCase;
use PlainDataTransformer\Transform;

class PrepareQueryTest extends TestCase
{
    public function testShouldReturnDesiredSQL()
    {
        // Given
        $connection = DriverManager::getConnection([
            'url' => Transform::toString($_ENV['DATABASE_URL'] ?? ''),
        ]);

        // When & Then
        $this->assertEquals(
            sprintf('SELECT * FROM %s WHERE refresh_token_uuid = :uuid', Token::getTableName()),
            (new FindTokenByRefreshTokenUuidQuery(
                $connection,
                Token::getTableName(),
                '60efd5f1-d831-4c02-863d-4ee11843fc2e',
            ))->getSQL()
        );
    }
}
