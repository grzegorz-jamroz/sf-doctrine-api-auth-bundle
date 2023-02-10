<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Variant\Controller;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Ifrost\ApiFoundation\ApiInterface;
use Ifrost\DoctrineApiAuthBundle\Tests\Variant\Utility\ApiRequestVariant;
use Ifrost\DoctrineApiBundle\Controller\DoctrineApiController;
use Ifrost\DoctrineApiBundle\Query\DbalQuery;
use Ifrost\DoctrineApiBundle\Utility\DbClient;
use PlainDataTransformer\Transform;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBag;
use Symfony\Component\HttpFoundation\Request;

class DoctrineApiControllerVariant extends DoctrineApiController
{
    public function __construct(?Request $request = null)
    {
        $container = new Container();
        $registry = new Registry(
            $container,
            [
                'default' => 'doctrine.dbal.default_connection',
            ],
            [
                'default' => 'doctrine.orm.default_entity_manager',
            ],
            'default',
            'default',
        );
        $conn = DriverManager::getConnection([
            'url' => Transform::toString($_ENV['DATABASE_URL'] ?? ''),
        ]);
        $container->set('doctrine.dbal.default_connection', $conn);
        $container->set('doctrine', $registry);
        $container->set('parameter_bag', new ContainerBag($container));
        $container->set('app.api_request', new ApiRequestVariant($request));
        $this->setContainer($container);
    }

    public function getContainer(): Container
    {
        return $this->container instanceof Container ? $this->container : new Container();
    }

    public function getDbal(): Connection
    {
        return parent::getDbal();
    }

    public function getDbClient(): DbClient
    {
        return parent::getDbClient();
    }

    /**
     * {@inheritDoc}
     */
    public function fetchColumn(string|DbalQuery $query, mixed ...$params): mixed
    {
        return parent::fetchColumn($query, ...$params);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchFirstColumn(string|DbalQuery $query, mixed ...$params): array
    {
        return parent::fetchFirstColumn($query, ...$params);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchOne(string|DbalQuery $query, mixed ...$params): array
    {
        return parent::fetchOne($query, ...$params);
    }

    /**
     * {@inheritDoc}
     */
    public function fetchAll(string|DbalQuery $query, mixed ...$params): array
    {
        return parent::fetchAll($query, ...$params);
    }

    public function getApi(string $entityClassName = ''): ApiInterface
    {
        return parent::getApi($entityClassName);
    }

    public function setDbalCacheAsFilesystemAdapter(): void
    {
        $cacheDir = sprintf(ABSPATH . '/var/doctrine/dbal');
        $cache = new FilesystemAdapter('', 0, $cacheDir);
        $this->container->set('ifrost_doctrine_api.dbal_cache_adapter', $cache);
    }
}
