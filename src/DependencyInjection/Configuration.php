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
            ->booleanNode('enabled')->defaultTrue()->end()
            ->booleanNode('enforce_collection_list')->defaultTrue()->end()
            ->end();

        return $treeBuilder;
    }
}
