<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\DependencyInjection\Compiler;

use Nexara\ApiPlatformVoter\Security\VoterRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class OptimizedVoterRegistryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->has(VoterRegistry::class)) {
            return;
        }

        $registry = $container->findDefinition(VoterRegistry::class);
        $taggedServices = $container->findTaggedServiceIds('nexara.api_voter');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (isset($attributes['resource'])) {
                    $definition = $container->getDefinition($id);
                    $voterClass = $definition->getClass();

                    if ($voterClass !== null) {
                        $registry->addMethodCall('register', [
                            $voterClass,
                            $attributes['resource'],
                        ]);
                    }
                }
            }
        }
    }
}
