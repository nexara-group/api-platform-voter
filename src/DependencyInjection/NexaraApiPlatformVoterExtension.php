<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\DependencyInjection;

use Nexara\ApiPlatformVoter\Security\Voter\AutoConfiguredCrudVoter;
use Nexara\ApiPlatformVoter\Security\VoterRegistry;
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
            ->addMethodCall('setVoterRegistry', [new Reference(VoterRegistry::class)])
            ->addMethodCall('setMetadataFactory', [new Reference('api_platform.metadata.resource.metadata_collection_factory')]);
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('nexara_api_platform_voter.enabled', $config['enabled']);
        $container->setParameter('nexara_api_platform_voter.enforce_collection_list', $config['enforce_collection_list']);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.php');
    }
}
