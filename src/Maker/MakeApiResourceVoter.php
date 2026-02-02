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
        return 'Generate a CrudVoter for an API Platform ApiResource and add #[Secured] attribute to the resource.';
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

        // Interactive custom operations
        if ($io->confirm('Do you want to add more custom operations interactively?', false)) {
            $customOps = $this->addInteractiveCustomOperations($io, $customOps);
        }

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

        // Generate tests
        if ($io->confirm('Generate tests for this voter?', true)) {
            $this->generateVoterTests($generator, $voterClassName, $resourceClass, $customOps, $prefix);
        }

        // Generate processors for custom operations
        if ($customOps !== [] && $io->confirm('Generate processor classes for custom operations?', false)) {
            $this->generateProcessors($generator, $io, $resourceClass, $customOps);
        }

        $io->success(sprintf(
            'Voter "%s" generated and #[Secured] attribute added to %s.',
            $voterClassName,
            $resourceClass,
        ));
    }

    private function addInteractiveCustomOperations(ConsoleStyle $io, array $existingOps): array
    {
        $operations = $existingOps;

        while (true) {
            $operationName = $io->ask('Enter custom operation name (or press Enter to finish)', null);

            if ($operationName === null || $operationName === '') {
                break;
            }

            if (! in_array($operationName, $operations, true)) {
                $operations[] = $operationName;
                $io->success("Added custom operation: {$operationName}");
            } else {
                $io->note("Operation {$operationName} already exists");
            }
        }

        return $operations;
    }

    private function generateVoterTests(
        Generator $generator,
        string $voterClassName,
        string $resourceClass,
        array $customOps,
        ?string $prefix = null
    ): void {
        $testClassName = $voterClassName . 'Test';
        $testFqcn = 'App\\Tests\\Security\\Voter\\' . $testClassName;
        
        $resourceShort = Str::getShortClassName($resourceClass);
        $finalPrefix = $prefix ?? strtolower($resourceShort);

        $generator->generateClass(
            $testFqcn,
            __DIR__ . '/../Resources/skeleton/VoterTest.tpl.php',
            [
                'voter_class' => 'App\\Security\\Voter\\' . $voterClassName,
                'voter_class_short' => $voterClassName,
                'resource_class' => $resourceClass,
                'resource_class_short' => $resourceShort,
                'custom_operations' => $customOps,
                'prefix' => $finalPrefix,
            ],
        );

        $generator->writeChanges();
    }

    private function generateProcessors(
        Generator $generator,
        ConsoleStyle $io,
        string $resourceClass,
        array $customOps
    ): void {
        $resourceShort = Str::getShortClassName($resourceClass);

        foreach ($customOps as $operation) {
            $processorName = $resourceShort . ucfirst($this->toCamelCase($operation)) . 'Processor';
            $processorFqcn = 'App\\State\\Processor\\' . $processorName;

            $generator->generateClass(
                $processorFqcn,
                __DIR__ . '/../Resources/skeleton/CustomOperationProcessor.tpl.php',
                [
                    'resource_class' => $resourceClass,
                    'operation_name' => $operation,
                ],
            );

            $io->text("Generated processor: {$processorName}");
        }

        $generator->writeChanges();
    }

    private function toCamelCase(string $str): string
    {
        $str = str_replace(['-', '_'], ' ', $str);
        $str = ucwords($str);

        return str_replace(' ', '', $str);
    }
}
