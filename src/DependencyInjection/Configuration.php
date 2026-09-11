<?php

declare(strict_types=1);

namespace Nexum\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('nexum');
        $root = $tree->getRootNode();
        $children = $root->children();
        $children->arrayNode('discovery')->addDefaultsIfNotSet()->children()
            ->scalarNode('root')->defaultValue('%kernel.project_dir%/src')->cannotBeEmpty()->end()
            ->scalarNode('namespace')->defaultNull()->end()
        ->end()->end();
        $children->arrayNode('modules')->useAttributeAsKey('id')->arrayPrototype()->children()
            ->booleanNode('enabled')->defaultTrue()->end()
        ->end()->end()->end();
        $children->arrayNode('projects')->useAttributeAsKey('id')->arrayPrototype()->children()
            ->arrayNode('hosts')->scalarPrototype()->cannotBeEmpty()->end()->defaultValue([])->end()
        ->end()->end()->end();
        $children->arrayNode('doctrine')->canBeDisabled()->end();
        $children->arrayNode('api')->canBeDisabled()->end();
        $children->arrayNode('user')->canBeEnabled()->children()
            ->scalarNode('entity_class')->defaultValue('App\\Entity\\User')->cannotBeEmpty()->end()
        ->end()->end();
        $children->arrayNode('filesystem')->canBeEnabled()->children()
            ->scalarNode('root')->defaultValue('%kernel.project_dir%/storage/files')->cannotBeEmpty()->end()
            ->integerNode('max_read_bytes')->defaultValue(10_485_760)->min(1)->end()
        ->end()->end();
        $children->arrayNode('mailer')->canBeEnabled()->children()
            ->scalarNode('default_from')->defaultNull()->end()
        ->end()->end();
        $children->arrayNode('messenger')->canBeEnabled()->end();
        $children->arrayNode('qrcode')->canBeEnabled()->end();

        $security = $children->arrayNode('security')->canBeDisabled()->children();
        $security->integerNode('access_token_ttl')->min(60)->defaultValue(3600)->end();
        $security->arrayNode('role_permissions')->useAttributeAsKey('role')->arrayPrototype()
            ->scalarPrototype()->cannotBeEmpty()->end()->end()->defaultValue([])->end();
        $this->addApiNode($security->arrayNode('api'));
        $mfa = $security->arrayNode('mfa')->canBeEnabled()->children();
        $mfa->scalarNode('issuer')->defaultValue('Nexum')->cannotBeEmpty()->end();
        $mfa->integerNode('challenge_ttl')->defaultValue(300)->min(60)->max(900)->end();
        $mfa->integerNode('max_attempts')->defaultValue(5)->min(1)->max(10)->end();
        $this->addApiNode($mfa->arrayNode('api'));
        $mfa->end()->end();
        $passkeys = $security->arrayNode('passkeys')->canBeEnabled()->children();
        $passkeys->arrayNode('relying_party')->children()
            ->scalarNode('rp_id')->defaultNull()->end()
            ->arrayNode('allowed_origins')->scalarPrototype()->cannotBeEmpty()->end()->defaultValue([])->end()
        ->end()->end();
        $passkeys->arrayNode('relying_parties')->useAttributeAsKey('project')->arrayPrototype()->children()
            ->scalarNode('rp_id')->isRequired()->cannotBeEmpty()->end()
            ->arrayNode('allowed_origins')->isRequired()->requiresAtLeastOneElement()->scalarPrototype()->cannotBeEmpty()->end()->end()
        ->end()->end()->end();
        $passkeys->integerNode('challenge_ttl')->defaultValue(300)->min(60)->max(900)->end();
        $passkeys->integerNode('max_attempts')->defaultValue(5)->min(1)->max(10)->end();
        $this->addApiNode($passkeys->arrayNode('api'));
        $passkeys->end()->end();
        $security->end()->end();

        $management = $children->arrayNode('management')->canBeEnabled()->children();
        $this->addApiNode($management->arrayNode('api'));
        $management->end()->end();
        $children->end();

        return $tree;
    }

    private function addApiNode(ArrayNodeDefinition $node): void
    {
        $node->canBeEnabled()->children()
            ->arrayNode('projects')->scalarPrototype()->cannotBeEmpty()->end()->defaultValue([])->end()
        ->end()->end();
    }
}
