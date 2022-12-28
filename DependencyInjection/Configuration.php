<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ifrost_doctrine_api_auth');
        /** @var ArrayNodeDefinition $definition */
        $definition = $treeBuilder->getRootNode();
        $builder = $definition->children();
        $builder->booleanNode('exception_listener')->defaultValue(true)->end();

        return $treeBuilder;
    }
}
