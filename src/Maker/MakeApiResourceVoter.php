<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Maker;

use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Nexara\ApiPlatformVoter\Maker\Util\CustomOperationExtractor;
use Nexara\ApiPlatformVoter\Maker\Util\PhpResourceVoterAttributeAdder;
use Nexara\ApiPlatformVoter\Maker\Util\ResourceClassFinder;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

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

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $finder = new ResourceClassFinder($this->projectDir);
        $resources = $finder->findApiResources();

        if ($resources === []) {
            $io->error('No ApiPlatform ApiResource classes were found.');

            return;
        }

        $helper = $io->getHelperSet()->get('question');
        $question = new ChoiceQuestion('Select an ApiResource:', $resources);
        $resourceClass = $helper->ask($input, $io, $question);

        if (! is_string($resourceClass) || $resourceClass === '') {
            $io->error('Invalid resource selection.');

            return;
        }

        $resourceShort = Str::getShortClassName($resourceClass);
        $defaultVoterClassName = $resourceShort . 'Voter';

        $voterClassQuestion = new Question('Voter class name', $defaultVoterClassName);
        $voterClassName = $helper->ask($input, $io, $voterClassQuestion);
        if (! is_string($voterClassName) || $voterClassName === '') {
            $io->error('Invalid voter class name.');

            return;
        }

        $prefixQuestion = new Question('Optional prefix (leave empty to omit prefix)', null);
        $prefix = $helper->ask($input, $io, $prefixQuestion);
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

        (new PhpResourceVoterAttributeAdder())->addToResourceClass(
            $resourceClass,
            $voterFqcn,
            $prefix,
        );

        $generator->writeChanges();

        $io->success('API Resource voter generated.');
    }
}
