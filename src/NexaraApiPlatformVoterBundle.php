<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter;

use Nexara\ApiPlatformVoter\DependencyInjection\Compiler\VoterRegistryCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class NexaraApiPlatformVoterBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new VoterRegistryCompilerPass());
        $container->addCompilerPass(new Compiler\VoterValidatorCompilerPass());
        $container->addCompilerPass(new Compiler\OptimizedVoterRegistryCompilerPass());
        $container->addCompilerPass(new Compiler\ProviderDecoratorCompilerPass());
    }
}
