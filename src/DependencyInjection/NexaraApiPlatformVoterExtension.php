<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\DependencyInjection;

use Nexara\ApiPlatformVoter\Security\VoterRegistry;
use Nexara\ApiPlatformVoter\Voter\AutoConfiguredCrudVoter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class NexaraApiPlatformVoterExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        // Register autoconfiguration for AutoConfiguredCrudVoter
        // This will apply to all services in the application that extend AutoConfiguredCrudVoter
        $container->registerForAutoconfiguration(AutoConfiguredCrudVoter::class)
            ->addMethodCall('setVoterRegistry', [new Reference(VoterRegistry::class)]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('nexara_api_platform_voter.enabled', $config['enabled']);
        $container->setParameter('nexara_api_platform_voter.enforce_collection_list', $config['enforce_collection_list']);
        $container->setParameter('nexara_api_platform_voter.strict_mode', $config['strict_mode']);
        $container->setParameter('nexara_api_platform_voter.debug', $config['debug']);
        $container->setParameter('nexara_api_platform_voter.debug_output', $config['debug_output']);
        $container->setParameter('nexara_api_platform_voter.audit_enabled', $config['audit']['enabled']);
        $container->setParameter('nexara_api_platform_voter.audit_level', $config['audit']['level']);
        $container->setParameter('nexara_api_platform_voter.audit_include_context', $config['audit']['include_context']);
        $container->setParameter('nexara_api_platform_voter.custom_providers_auto_secure', $config['custom_providers']['auto_secure']);
        $container->setParameter('nexara_api_platform_voter.secure_custom_providers', $config['custom_providers']['secure']);
        $container->setParameter('nexara_api_platform_voter.skip_custom_providers', $config['custom_providers']['skip']);
        $container->setParameter('nexara_api_platform_voter.operation_patterns', $config['operation_mapping']['custom_operation_patterns']);
        $container->setParameter('nexara_api_platform_voter.naming_convention', $config['operation_mapping']['naming_convention']);
        $container->setParameter('nexara_api_platform_voter.normalize_names', $config['operation_mapping']['normalize_names']);
        $container->setParameter('nexara_api_platform_voter.detect_by_uri', $config['operation_mapping']['detect_by_uri']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');
    }
}
