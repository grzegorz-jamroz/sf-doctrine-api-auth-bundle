<?php

declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\HttpFoundation\Request;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ifrost_doctrine_api_auth');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->booleanNode('exception_listener')
                    ->defaultValue(true)
                ->end()
                ->scalarNode('token_entity')
                    ->defaultNull()
                ->end()
                ->scalarNode('user_entity')
                    ->defaultNull()
                ->end()
                ->booleanNode('return_user_in_body')
                    ->defaultValue(false)
                ->end()
                ->booleanNode('return_refresh_token_in_body')
                    ->defaultValue(false)
                ->end()
                ->integerNode('ttl')
                    ->defaultValue(2592000)
                    ->info('The default TTL for all authenticators.')
                ->end()
                ->scalarNode('token_parameter_name')
                    ->defaultValue('refresh_token')
                    ->info('The default request parameter name containing the refresh token for all authenticators.')
                ->end()
                ->arrayNode('routes')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('logout')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('path')->defaultValue('/logout')->end()
                                ->scalarNode('name')->defaultValue('logout')->end()
                                ->arrayNode('methods')
                                    ->defaultValue([Request::METHOD_POST])->scalarPrototype()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('refresh_token')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('path')->defaultValue('token/refresh')->end()
                                ->scalarNode('name')->defaultValue('refresh_token')->end()
                                ->arrayNode('methods')
                                    ->defaultValue([Request::METHOD_POST])->scalarPrototype()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('refresh_token_action')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('validate_jwt')->defaultValue(false)->end()
                        ->booleanNode('after_get_user_data_subscriber')->defaultValue(true)->end()
                    ->end()
                ->end()
                ->arrayNode('cookie')
                    ->addDefaultsIfNotSet()
                    ->canBeEnabled()
                        ->children()
                            ->enumNode('same_site')
                            ->values(['none', 'lax', 'strict'])
                            ->defaultValue('lax')
                        ->end()
                        ->scalarNode('path')->defaultValue('/')->cannotBeEmpty()->end()
                        ->scalarNode('domain')->defaultNull()->end()
                        ->scalarNode('http_only')->defaultTrue()->end()
                        ->scalarNode('secure')->defaultTrue()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
