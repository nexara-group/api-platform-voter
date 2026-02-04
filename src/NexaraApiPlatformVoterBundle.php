<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter;

use Nexara\ApiPlatformVoter\DependencyInjection\Compiler\OptimizedVoterRegistryCompilerPass;
use Nexara\ApiPlatformVoter\DependencyInjection\Compiler\ProviderDecoratorCompilerPass;
use Nexara\ApiPlatformVoter\DependencyInjection\Compiler\TenantIsolationCompilerPass;
use Nexara\ApiPlatformVoter\DependencyInjection\Compiler\VoterConfigurationCompilerPass;
use Nexara\ApiPlatformVoter\DependencyInjection\Compiler\VoterRegistryCompilerPass;
use Nexara\ApiPlatformVoter\DependencyInjection\Compiler\VoterValidatorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class NexaraApiPlatformVoterBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new VoterRegistryCompilerPass());
        $container->addCompilerPass(new VoterValidatorCompilerPass());
        $container->addCompilerPass(new OptimizedVoterRegistryCompilerPass());
        $container->addCompilerPass(new VoterConfigurationCompilerPass());
        $container->addCompilerPass(new ProviderDecoratorCompilerPass());
        $container->addCompilerPass(new TenantIsolationCompilerPass());
    }
}
