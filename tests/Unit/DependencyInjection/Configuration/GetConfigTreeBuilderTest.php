<?php
declare(strict_types=1);

namespace Ifrost\DoctrineApiAuthBundle\Tests\Unit\DependencyInjection\Configuration;

use Ifrost\DoctrineApiAuthBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;

class GetConfigTreeBuilderTest extends TestCase
{
    public function testShouldReturnDefaultTreeBuilder()
    {
        // Given
        $children = [
            'exception_listener',
            'token_entity',
            'user_entity',
            'ttl',
            'token_parameter_name',
            'cookie',
        ];
        $treeBuilder = (new Configuration())->getConfigTreeBuilder();

        // When & Then
        foreach ($children as $child) {
            $definition = $treeBuilder->getRootNode()->find($child);
            $this->assertInstanceOf(NodeDefinition::class, $definition);
        }
    }
}






