<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\DependencyInjection\Compiler;

use Nexara\ApiPlatformVoter\ApiPlatform\State\CustomProviderDecorator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Automatically decorates custom API Platform providers with security checks.
 */
final class ProviderDecoratorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasParameter('nexara_api_platform_voter.enabled')) {
            return;
        }

        $enabled = $container->getParameter('nexara_api_platform_voter.enabled');
        if (! $enabled) {
            return;
        }

        // Find all services tagged as API Platform providers
        $providers = $container->findTaggedServiceIds('api_platform.state_provider');

        foreach ($providers as $id => $tags) {
            // Skip the main provider (already decorated by SecurityProvider)
            if ($id === 'api_platform.state_provider.main') {
                continue;
            }

            // Skip if already decorated
            if (str_starts_with($id, '.inner')) {
                continue;
            }

            // Check if this provider should be secured
            if ($this->shouldDecorate($container, $id, $tags)) {
                $this->decorateProvider($container, $id);
            }
        }
    }

    private function shouldDecorate(ContainerBuilder $container, string $id, array $tags): bool
    {
        // Check configuration for specific providers to secure/skip
        $secureProviders = $container->hasParameter('nexara_api_platform_voter.secure_custom_providers')
            ? $container->getParameter('nexara_api_platform_voter.secure_custom_providers')
            : [];

        $skipProviders = $container->hasParameter('nexara_api_platform_voter.skip_custom_providers')
            ? $container->getParameter('nexara_api_platform_voter.skip_custom_providers')
            : [];

        // Skip if explicitly excluded
        if (is_array($skipProviders) && in_array($id, $skipProviders, true)) {
            return false;
        }

        // Secure if explicitly included
        if (is_array($secureProviders) && $secureProviders !== [] && in_array($id, $secureProviders, true)) {
            return true;
        }

        // By default, secure all custom providers
        return true;
    }

    private function decorateProvider(ContainerBuilder $container, string $id): void
    {
        $decoratorId = $id . '.security_decorator';

        $decorator = new Definition(CustomProviderDecorator::class);
        $decorator->setDecoratedService($id);
        $decorator->setArguments([
            new Reference('.inner'),
            new Reference('nexara_api_platform_voter.operation_mapper'),
            new Reference('nexara_api_platform_voter.metadata_resolver'),
            new Reference('nexara_api_platform_voter.subject_resolver'),
            new Reference('security.authorization_checker'),
            '%nexara_api_platform_voter.enabled%',
        ]);

        $container->setDefinition($decoratorId, $decorator);
    }
}
