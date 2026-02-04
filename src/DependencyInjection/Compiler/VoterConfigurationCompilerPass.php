<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\DependencyInjection\Compiler;

use Nexara\ApiPlatformVoter\Security\VoterRegistry;
use Nexara\ApiPlatformVoter\Voter\CrudVoter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Injects VoterRegistry into all CrudVoter instances.
 *
 * This enables autoConfigure() functionality for all voters.
 */
final class VoterConfigurationCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->has(VoterRegistry::class)) {
            return;
        }

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();

            if ($class === null) {
                continue;
            }

            // Skip if class doesn't exist
            if (! class_exists($class)) {
                continue;
            }

            // Check if it's a CrudVoter
            if (! is_subclass_of($class, CrudVoter::class)) {
                continue;
            }

            // Inject VoterRegistry
            $definition->addMethodCall('setVoterRegistry', [
                new Reference(VoterRegistry::class),
            ]);
        }
    }
}
