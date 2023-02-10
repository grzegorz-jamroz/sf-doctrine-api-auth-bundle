<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Query;

use Doctrine\DBAL\Connection;
use Ifrost\DoctrineApiBundle\Query\DbalQuery;

class FindTokenByRefreshTokenUuidQuery extends DbalQuery
{
    public function __construct(
        readonly Connection $connection,
        readonly private string $tableName,
        readonly private string $uuid,
    ) {
        parent::__construct($connection);
    }

    protected function prepareQuery(): void
    {
        $this->select('*');
        $this->from($this->tableName);
        $this->where('refresh_token_uuid = :uuid');
        $this->setParameter('uuid', $this->uuid);
    }
}
