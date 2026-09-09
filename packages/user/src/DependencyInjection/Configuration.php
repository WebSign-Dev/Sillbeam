<?php

declare(strict_types=1);

namespace Nexum\User\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nexum_user');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('entity_class')
                    ->cannotBeEmpty()
                    ->defaultValue('App\\Entity\\User')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
