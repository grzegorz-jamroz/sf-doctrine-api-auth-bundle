<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\DependencyInjection;

use Ifrost\DoctrineApiAuthBundle\Entity\ApiUserInterface;
use Ifrost\DoctrineApiAuthBundle\Entity\TokenInterface;
use PlainDataTransformer\Transform;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class IfrostDoctrineApiAuthExtension extends Extension
{
    /**
     * @param array<int|string, mixed> $configs
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('ifrost_doctrine_api_auth.exception_listener', $config['exception_listener']);
        $container->setParameter('ifrost_doctrine_api_auth.ttl', $config['ttl']);
        $container->setParameter('ifrost_doctrine_api_auth.token_parameter_name', $config['token_parameter_name']);
        $container->setParameter('ifrost_doctrine_api_auth.refresh_token_action.validate_jwt', $config['refresh_token_action']['validate_jwt']);
        $container->setParameter('ifrost_doctrine_api_auth.refresh_token_action.after_get_user_data_subscriber', $config['refresh_token_action']['after_get_user_data_subscriber']);
        $container->setParameter('ifrost_doctrine_api_auth.return_user_in_body', $config['return_user_in_body']);
        $container->setParameter('ifrost_doctrine_api_auth.return_refresh_token_in_body', $config['return_refresh_token_in_body']);
        $container->setParameter('ifrost_doctrine_api_auth.cookie', $config['cookie'] ?? []);
        $container->setParameter('ifrost_doctrine_api_auth.routes', $config['routes']);

        $this->setTokenEntity($config, $container);
        $this->setUserEntity($config, $container);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        $loader->load('services.yaml');

        if (Transform::toBool($config['exception_listener'])) {
            $container->prependExtensionConfig('ifrost_api', ['exception_listener' => false]);
            $loader->load('exception_listener.yaml');
        }

        if (Transform::toBool($config['refresh_token_action']['after_get_user_data_subscriber'])) {
            $loader->load('token_refresh_after_get_user_data_subscriber.yaml');
        }
    }

    /**
     * @param array<int|string, mixed> $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new Configuration();
    }

    /**
     * @param array<string, mixed> $config
     */
    private function setTokenEntity(array $config, ContainerBuilder $container): void
    {
        if (Transform::toString($config['token_entity'] ?? '') !== '') {
            $this->setEntity('token_entity', TokenInterface::class, $config, $container);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function setUserEntity(array $config, ContainerBuilder $container): void
    {
        if (Transform::toString($config['user_entity'] ?? '') !== '') {
            $this->setEntity('user_entity', ApiUserInterface::class, $config, $container);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function setEntity(
        string $name,
        string $className,
        array $config,
        ContainerBuilder $container
    ): void {
        $entityClassName = Transform::toString($config[$name] ?? '');

        if (!in_array($className, Transform::toArray(class_implements($entityClassName)))) {
            throw new \InvalidArgumentException(sprintf('Setting %s (%s) has to implement "%s" interface.', $name, $entityClassName, $className));
        }

        $container->setParameter(sprintf('ifrost_doctrine_api_auth.%s', $name), $entityClassName);
    }
}
