<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Maker;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Nexara\ApiPlatformVoter\Maker\Util\CustomOperationExtractor;
use Nexara\ApiPlatformVoter\Maker\Util\PhpResourceVoterAttributeAdder;
use Nexara\ApiPlatformVoter\Maker\Util\ResourceClassFinder;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

final class MakeApiResourceVoter extends AbstractMaker
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataCollectionFactory,
        private readonly string $projectDir,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:api-resource-voter';
    }

    public static function getCommandDescription(): string
    {
        return 'Generate a CrudVoter for an API Platform ApiResource and add #[ApiResourceVoter] attribute to the resource.';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $finder = new ResourceClassFinder($this->projectDir);
        $resources = $finder->findApiResources();

        if ($resources === []) {
            $io->error('No ApiPlatform ApiResource classes were found.');

            return;
        }

        $resourceClass = $io->choice('Select an ApiResource:', $resources);

        if (! is_string($resourceClass) || $resourceClass === '') {
            $io->error('Invalid resource selection.');

            return;
        }

        $resourceShort = Str::getShortClassName($resourceClass);
        $defaultVoterClassName = $resourceShort . 'Voter';

        $voterClassName = $io->ask('Voter class name', $defaultVoterClassName);
        if (! is_string($voterClassName) || $voterClassName === '') {
            $io->error('Invalid voter class name.');

            return;
        }

        $prefix = $io->ask('Optional prefix (leave empty to omit prefix)', null);
        $prefix = is_string($prefix) && $prefix !== '' ? $prefix : null;

        $voterFqcn = 'App\\Security\\Voter\\' . $voterClassName;

        $customOps = (new CustomOperationExtractor())->extract(
            $this->resourceMetadataCollectionFactory,
            $resourceClass,
        );

        $generator->generateClass(
            $voterFqcn,
            __DIR__ . '/../Resources/skeleton/ApiResourceVoter.tpl.php',
            [
                'resource_class' => $resourceClass,
                'custom_operations' => $customOps,
            ],
        );

        $generator->writeChanges();

        (new PhpResourceVoterAttributeAdder())->addToResourceClass(
            $resourceClass,
            $voterFqcn,
            $prefix,
        );

        $io->success(sprintf(
            'Voter "%s" generated and #[ApiResourceVoter] attribute added to %s.',
            $voterClassName,
            $resourceClass,
        ));
    }
}
