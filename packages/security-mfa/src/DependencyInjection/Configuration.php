<?php

declare(strict_types=1);

namespace Nexum\Security\Mfa\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('nexum_security_mfa');
        $tree->getRootNode()
            ->children()
                ->scalarNode('issuer')->defaultValue('Nexum')->cannotBeEmpty()->end()
                ->integerNode('challenge_ttl')->defaultValue(300)->min(60)->max(900)->end()
                ->integerNode('max_attempts')->defaultValue(5)->min(1)->max(10)->end()
            ->end();

        return $tree;
    }
}
