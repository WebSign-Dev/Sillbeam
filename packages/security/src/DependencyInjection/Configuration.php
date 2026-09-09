<?php

declare(strict_types=1);

namespace Nexum\Security\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nexum_security');

        $treeBuilder->getRootNode()
            ->children()
                ->integerNode('access_token_ttl')
                    ->min(60)
                    ->defaultValue(3600)
                ->end()
                ->arrayNode('role_permissions')
                    ->useAttributeAsKey('role')
                    ->arrayPrototype()
                        ->scalarPrototype()->cannotBeEmpty()->end()
                    ->end()
                    ->defaultValue([])
                ->end()
            ->end();

        return $treeBuilder;
    }
}
