<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Command;

use Nexara\ApiPlatformVoter\Attribute\Secured;
use Nexara\ApiPlatformVoter\Security\VoterRegistry;
use Nexara\ApiPlatformVoter\Voter\CrudVoter;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Validates voter implementations and their configuration.
 */
#[AsCommand(
    name: 'voter:validate',
    description: 'Validate voter implementations and configuration'
)]
final class VoterValidateCommand extends Command
{
    private const CRUD_METHODS = ['canList', 'canCreate', 'canRead', 'canUpdate', 'canDelete'];

    public function __construct(
        private readonly VoterRegistry $voterRegistry,
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('detailed', 'd', InputOption::VALUE_NONE, 'Show detailed validation results')
            ->addOption('voter', null, InputOption::VALUE_REQUIRED, 'Validate specific voter class');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔍 Voter Validation');

        $specificVoter = $input->getOption('voter');
        $detailed = $input->getOption('detailed');

        $voters = $this->findVoters($specificVoter);

        if ($voters === []) {
            $io->warning('No voters found to validate.');
            return Command::SUCCESS;
        }

        $totalVoters = count($voters);
        $validVoters = 0;
        $issues = [];

        foreach ($voters as $voterClass) {
            $validation = $this->validateVoter($voterClass, $detailed);

            if ($validation['valid']) {
                $validVoters++;
                $io->writeln(sprintf('✅ <info>%s</info>', $this->getShortClassName($voterClass)));

                if ($detailed && ! empty($validation['details'])) {
                    foreach ($validation['details'] as $detail) {
                        $io->writeln(sprintf('   • %s', $detail));
                    }
                }
            } else {
                $io->writeln(sprintf('❌ <error>%s</error>', $this->getShortClassName($voterClass)));

                if (! empty($validation['errors'])) {
                    foreach ($validation['errors'] as $error) {
                        $io->writeln(sprintf('   • <comment>%s</comment>', $error));
                    }
                }

                $issues[$voterClass] = $validation['errors'];
            }

            if (! empty($validation['warnings'])) {
                foreach ($validation['warnings'] as $warning) {
                    $io->writeln(sprintf('   ⚠  %s', $warning));
                }
            }

            $io->newLine();
        }

        $io->section('📊 Summary');

        $io->table(
            ['Metric', 'Value'],
            [
                ['Total voters', $totalVoters],
                ['Valid voters', $validVoters],
                ['Voters with issues', $totalVoters - $validVoters],
                ['Success rate', sprintf('%.1f%%', ($validVoters / $totalVoters) * 100)],
            ]
        );

        if ($issues !== []) {
            $io->section('🔧 Recommendations');

            foreach ($issues as $voterClass => $errors) {
                $io->writeln(sprintf('<comment>%s</comment>', $this->getShortClassName($voterClass)));

                foreach ($errors as $error) {
                    $recommendation = $this->getRecommendation($error);
                    if ($recommendation !== null) {
                        $io->writeln(sprintf('  💡 %s', $recommendation));
                    }
                }

                $io->newLine();
            }
        }

        if ($validVoters === $totalVoters) {
            $io->success('All voters are properly configured! 🎉');
            return Command::SUCCESS;
        }

        $io->warning(sprintf('%d voter(s) need attention.', $totalVoters - $validVoters));

        return Command::FAILURE;
    }

    private function findVoters(?string $specificVoter): array
    {
        if ($specificVoter !== null) {
            if (class_exists($specificVoter)) {
                return [$specificVoter];
            }
            return [];
        }

        $mappings = $this->voterRegistry->getAllMappings();
        return array_keys($mappings);
    }

    private function validateVoter(string $voterClass, bool $detailed): array
    {
        $result = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'details' => [],
        ];

        if (! class_exists($voterClass)) {
            $result['valid'] = false;
            $result['errors'][] = 'Class does not exist';
            return $result;
        }

        try {
            $reflection = new ReflectionClass($voterClass);

            // Check if extends CrudVoter
            if (! $reflection->isSubclassOf(CrudVoter::class)) {
                $result['warnings'][] = 'Does not extend CrudVoter';
            }

            // Check CRUD method implementations
            $implementedMethods = [];
            $missingMethods = [];

            foreach (self::CRUD_METHODS as $method) {
                if ($this->hasOwnImplementation($reflection, $method)) {
                    $implementedMethods[] = $method;

                    if ($detailed) {
                        $result['details'][] = sprintf('Implements %s()', $method);
                    }
                } else {
                    $missingMethods[] = $method;
                }
            }

            if (empty($implementedMethods)) {
                $result['warnings'][] = 'No CRUD methods implemented (using defaults)';
            }

            // Check for custom operation methods
            $customMethods = $this->findCustomOperationMethods($reflection);
            if (! empty($customMethods)) {
                if ($detailed) {
                    foreach ($customMethods as $method) {
                        $result['details'][] = sprintf('Custom operation: %s()', $method);
                    }
                }
            }

            // Check if registered in VoterRegistry
            $resourceClass = $this->voterRegistry->getResourceClass($voterClass);
            if ($resourceClass !== null) {
                if ($detailed) {
                    $result['details'][] = sprintf('Registered for: %s', $this->getShortClassName($resourceClass));
                }

                // Check if resource has #[Secured] attribute
                if (class_exists($resourceClass)) {
                    $resourceReflection = new ReflectionClass($resourceClass);
                    $securedAttributes = $resourceReflection->getAttributes(Secured::class);

                    if (empty($securedAttributes)) {
                        $result['errors'][] = sprintf('Resource %s missing #[Secured] attribute', $this->getShortClassName($resourceClass));
                        $result['valid'] = false;
                    }
                }
            } else {
                $result['warnings'][] = 'Not registered in VoterRegistry';
            }

            // Check for potential issues in method signatures
            foreach ($reflection->getMethods(ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PUBLIC) as $method) {
                if (str_starts_with($method->getName(), 'can')) {
                    $this->validateMethodSignature($method, $result);
                }
            }

            // Check for test coverage
            $testExists = $this->checkTestCoverage($voterClass);
            if (! $testExists) {
                $result['warnings'][] = 'No test coverage found';
            } elseif ($detailed) {
                $result['details'][] = 'Test coverage: ✓';
            }

        } catch (\ReflectionException $e) {
            $result['valid'] = false;
            $result['errors'][] = sprintf('Reflection error: %s', $e->getMessage());
        }

        return $result;
    }

    private function hasOwnImplementation(ReflectionClass $class, string $methodName): bool
    {
        if (! $class->hasMethod($methodName)) {
            return false;
        }

        $method = $class->getMethod($methodName);

        // Check if method is declared in this class (not inherited)
        return $method->getDeclaringClass()->getName() === $class->getName();
    }

    private function findCustomOperationMethods(ReflectionClass $class): array
    {
        $customMethods = [];

        foreach ($class->getMethods(ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PUBLIC) as $method) {
            $name = $method->getName();

            if (str_starts_with($name, 'can') &&
                ! in_array($name, self::CRUD_METHODS, true) &&
                $name !== 'canCustomOperation' &&
                $method->getDeclaringClass()->getName() === $class->getName()) {
                $customMethods[] = $name;
            }
        }

        return $customMethods;
    }

    private function validateMethodSignature(ReflectionMethod $method, array &$result): void
    {
        $params = $method->getParameters();

        // canList should have no parameters
        if ($method->getName() === 'canList' && count($params) > 0) {
            $result['warnings'][] = sprintf('%s() should not have parameters', $method->getName());
        }

        // canCreate should have one parameter ($object)
        if ($method->getName() === 'canCreate' && count($params) !== 1) {
            $result['warnings'][] = sprintf('%s() should have exactly one parameter ($object)', $method->getName());
        }

        // canUpdate should have two parameters ($object, $previousObject)
        if ($method->getName() === 'canUpdate' && count($params) !== 2) {
            $result['warnings'][] = sprintf('%s() should have exactly two parameters ($object, $previousObject)', $method->getName());
        }
    }

    private function checkTestCoverage(string $voterClass): bool
    {
        $testClass = str_replace('\\Voter\\', '\\Tests\\Voter\\', $voterClass) . 'Test';

        if (class_exists($testClass)) {
            return true;
        }

        // Check in tests directory
        $classPath = str_replace('\\', '/', $voterClass);
        $testPath = $this->projectDir . '/tests/' . str_replace('App/', '', $classPath) . 'Test.php';

        return file_exists($testPath);
    }

    private function getRecommendation(string $error): ?string
    {
        if (str_contains($error, 'missing #[Secured] attribute')) {
            return 'Add #[Secured(voter: YourVoter::class)] to the resource class';
        }

        if (str_contains($error, 'does not exist')) {
            return 'Ensure the voter class file exists and is autoloaded';
        }

        return null;
    }

    private function getShortClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);
        return end($parts);
    }
}
