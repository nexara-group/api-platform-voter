<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Nexara\ApiPlatformVoter\ApiPlatform\Security\OperationToVoterAttributeMapper;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\OperationToVoterAttributeMapperInterface;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\SubjectResolver;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\SubjectResolverInterface;
use Nexara\ApiPlatformVoter\Audit\AuditLogger;
use Nexara\ApiPlatformVoter\Audit\AuditLoggerInterface;
use Nexara\ApiPlatformVoter\DataCollector\VoterDataCollector;
use Nexara\ApiPlatformVoter\Debug\VoterDebugger;
use Nexara\ApiPlatformVoter\Maker\MakeApiResourceVoter;
use Nexara\ApiPlatformVoter\Metadata\ResourceAccessMetadataResolver;
use Nexara\ApiPlatformVoter\Metadata\ResourceAccessMetadataResolverInterface;
use Nexara\ApiPlatformVoter\Processor\SecurityProcessor;
use Nexara\ApiPlatformVoter\Provider\SecurityProvider;
use Nexara\ApiPlatformVoter\Security\VoterRegistry;
use Nexara\ApiPlatformVoter\Voter\AutoConfiguredCrudVoter;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // Auto-configure AutoConfiguredCrudVoter instances
    $services->instanceof(AutoConfiguredCrudVoter::class)
        ->call('setVoterRegistry', [service(VoterRegistry::class)]);

    $services->set(OperationToVoterAttributeMapper::class)
        ->args([
            param('nexara_api_platform_voter.enforce_collection_list'),
            param('nexara_api_platform_voter.operation_patterns'),
            param('nexara_api_platform_voter.naming_convention'),
            param('nexara_api_platform_voter.normalize_names'),
            param('nexara_api_platform_voter.detect_by_uri'),
        ]);

    $services->alias(OperationToVoterAttributeMapperInterface::class, OperationToVoterAttributeMapper::class);
    $services->alias('nexara_api_platform_voter.operation_mapper', OperationToVoterAttributeMapper::class);

    $services->set(VoterRegistry::class)
        ->public();

    $services->set(VoterDebugger::class)
        ->args([
            service('logger')->nullOnInvalid(),
            param('nexara_api_platform_voter.debug'),
        ])
        ->call('enable', [])
        ->tag('monolog.logger', [
            'channel' => 'security',
        ]);

    $services->set(AuditLogger::class)
        ->args([
            service('logger')->nullOnInvalid(),
            param('nexara_api_platform_voter.audit_enabled'),
            param('nexara_api_platform_voter.audit_level'),
            param('nexara_api_platform_voter.audit_include_context'),
        ])
        ->tag('monolog.logger', [
            'channel' => 'audit',
        ]);

    $services->alias(AuditLoggerInterface::class, AuditLogger::class);

    $services->set(VoterDataCollector::class)
        ->args([
            service(VoterDebugger::class),
        ])
        ->tag('data_collector', [
            'template' => '@NexaraApiPlatformVoter/Collector/voter.html.twig',
            'id' => 'nexara.voter',
        ]);

    $services->set(SubjectResolver::class);
    $services->alias(SubjectResolverInterface::class, SubjectResolver::class);
    $services->alias('nexara_api_platform_voter.subject_resolver', SubjectResolver::class);

    $services->set(ResourceAccessMetadataResolver::class)
        ->args([
            service('cache.app')->nullOnInvalid(),
        ]);
    $services->alias(ResourceAccessMetadataResolverInterface::class, ResourceAccessMetadataResolver::class);
    $services->alias('nexara_api_platform_voter.metadata_resolver', ResourceAccessMetadataResolver::class);

    $services->set(SecurityProvider::class)
        ->decorate('api_platform.state_provider.main')
        ->args([
            service('.inner'),
            service(OperationToVoterAttributeMapperInterface::class),
            service(ResourceAccessMetadataResolverInterface::class),
            service(SubjectResolverInterface::class),
            service('security.authorization_checker'),
            param('nexara_api_platform_voter.enabled'),
            param('nexara_api_platform_voter.strict_mode'),
            service(VoterDebugger::class)->nullOnInvalid(),
            service(AuditLoggerInterface::class)->nullOnInvalid(),
            service('security.helper')->nullOnInvalid(),
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
            param('nexara_api_platform_voter.strict_mode'),
            service(VoterDebugger::class)->nullOnInvalid(),
            service(AuditLoggerInterface::class)->nullOnInvalid(),
            service('security.helper')->nullOnInvalid(),
        ]);

    if (class_exists(\Symfony\Bundle\MakerBundle\Maker\AbstractMaker::class)) {
        $services->set(MakeApiResourceVoter::class)
            ->args([
                service('api_platform.metadata.resource.metadata_collection_factory'),
                param('kernel.project_dir'),
            ])
            ->tag('maker.command');
    }
};
