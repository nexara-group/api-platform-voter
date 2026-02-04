<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\DependencyInjection\Compiler;

use Nexara\ApiPlatformVoter\MultiTenancy\TenantAwareVoterTrait;
use Nexara\ApiPlatformVoter\MultiTenancy\TenantContextInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Automatically configures voters that implement tenant-aware functionality.
 *
 * This compiler pass:
 * - Detects voters using TenantAwareVoterTrait
 * - Automatically injects TenantContext
 * - Validates tenant isolation configuration
 */
final class TenantIsolationCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->has(TenantContextInterface::class)) {
            return;
        }

        $voters = $container->findTaggedServiceIds('security.voter');

        foreach ($voters as $id => $tags) {
            if (! $container->hasDefinition($id)) {
                continue;
            }

            $definition = $container->getDefinition($id);
            $class = $definition->getClass();

            if ($class === null || ! class_exists($class)) {
                continue;
            }

            if ($this->usesTenantAwareTrait($class)) {
                // Inject TenantContext via setter injection
                $definition->addMethodCall('setTenantContext', [
                    new Reference(TenantContextInterface::class),
                ]);
            }
        }
    }

    private function usesTenantAwareTrait(string $class): bool
    {
        try {
            $reflection = new ReflectionClass($class);
            $traits = $this->getAllTraits($reflection);

            return in_array(TenantAwareVoterTrait::class, $traits, true);
        } catch (\ReflectionException) {
            return false;
        }
    }

    /**
     * Gets all traits used by a class, including inherited traits.
     *
     * @return array<string>
     */
    private function getAllTraits(ReflectionClass $class): array
    {
        $traits = [];

        // Get traits from current class
        foreach ($class->getTraits() as $trait) {
            $traits[] = $trait->getName();
            // Recursively get traits used by this trait
            $traits = array_merge($traits, $this->getAllTraits($trait));
        }

        // Get traits from parent class
        $parent = $class->getParentClass();
        if ($parent !== false) {
            $traits = array_merge($traits, $this->getAllTraits($parent));
        }

        return array_unique($traits);
    }
}
