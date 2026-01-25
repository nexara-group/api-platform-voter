<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('nexara_api_platform_voter');

        $treeBuilder->getRootNode()
            ->children()
            ->booleanNode('enabled')
            ->info('Enable/disable the voter bundle globally')
            ->defaultTrue()
            ->end()
            ->booleanNode('enforce_collection_list')
            ->info('Enforce authorization checks for collection list operations')
            ->defaultTrue()
            ->end()
            ->booleanNode('strict_mode')
            ->info('Throw exception if no voter supports the attribute (instead of denying silently)')
            ->defaultFalse()
            ->end()
            ->booleanNode('debug')
            ->info('Enable debug mode with detailed authorization decision logging')
            ->defaultFalse()
            ->end()
            ->integerNode('cache_ttl')
            ->info('Cache TTL in seconds for metadata resolution (0 to disable)')
            ->defaultValue(3600)
            ->min(0)
            ->end()
            ->end();

        return $treeBuilder;
    }
}
