<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Nexara\ApiPlatformVoter\ApiPlatform\Security\OperationToVoterAttributeMapper;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\OperationToVoterAttributeMapperInterface;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\ResourceAccessMetadataResolver;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\ResourceAccessMetadataResolverInterface;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\SubjectResolver;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\SubjectResolverInterface;
use Nexara\ApiPlatformVoter\ApiPlatform\State\SecurityProcessor;
use Nexara\ApiPlatformVoter\ApiPlatform\State\SecurityProvider;
use Nexara\ApiPlatformVoter\Maker\MakeApiResourceVoter;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(OperationToVoterAttributeMapper::class)
        ->args([
            param('nexara_api_platform_voter.enforce_collection_list'),
        ]);

    $services->alias(OperationToVoterAttributeMapperInterface::class, OperationToVoterAttributeMapper::class);

    $services->set(SubjectResolver::class);
    $services->alias(SubjectResolverInterface::class, SubjectResolver::class);

    $services->set(ResourceAccessMetadataResolver::class)
        ->args([
            service('cache.app')->nullOnInvalid(),
        ]);
    $services->alias(ResourceAccessMetadataResolverInterface::class, ResourceAccessMetadataResolver::class);

    $services->set(SecurityProvider::class)
        ->decorate('api_platform.state_provider.main')
        ->args([
            service('.inner'),
            service(OperationToVoterAttributeMapperInterface::class),
            service(ResourceAccessMetadataResolverInterface::class),
            service(SubjectResolverInterface::class),
            service('security.authorization_checker'),
            param('nexara_api_platform_voter.enabled'),
        ]);

    $services->set(SecurityProcessor::class)
        ->decorate('api_platform.state_processor.main')
        ->args([
            service('.inner'),
            service(OperationToVoterAttributeMapperInterface::class),
            service(ResourceAccessMetadataResolverInterface::class),
            service(SubjectResolverInterface::class),
            service('security.authorization_checker'),
            param('nexara_api_platform_voter.enabled'),
        ]);

    if (class_exists('Symfony\\Bundle\\MakerBundle\\Maker\\AbstractMaker')) {
        $services->set(MakeApiResourceVoter::class)
            ->args([
                service('api_platform.metadata.resource.metadata_collection_factory'),
                param('kernel.project_dir'),
            ])
            ->tag('maker.command');
    }
};
