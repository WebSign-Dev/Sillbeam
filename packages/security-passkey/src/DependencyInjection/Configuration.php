<?php

declare(strict_types=1);

namespace Nexum\Security\Passkey\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('nexum_security_passkey');
        $tree->getRootNode()
            ->children()
                ->scalarNode('rp_id')->isRequired()->cannotBeEmpty()->end()
                ->arrayNode('allowed_origins')
                    ->isRequired()->requiresAtLeastOneElement()->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
                ->integerNode('challenge_ttl')->defaultValue(300)->min(60)->max(900)->end()
                ->integerNode('max_attempts')->defaultValue(5)->min(1)->max(10)->end()
            ->end();

        return $tree;
    }
}
