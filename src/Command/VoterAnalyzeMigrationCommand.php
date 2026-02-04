<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Command;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Analyzes API Platform resources and suggests migration to voter-based security.
 */
#[AsCommand(
    name: 'voter:analyze-migration',
    description: 'Analyze API Platform resources and suggest migration to voter-based security'
)]
final class VoterAnalyzeMigrationCommand extends Command
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
        private readonly string $projectDir
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('🔍 API Platform Voter Migration Analysis');

        $resources = $this->findApiResources();

        if ($resources === []) {
            $io->success('No API Platform resources found.');
            return Command::SUCCESS;
        }

        $withNativeSecurity = [];
        $withVoterSecurity = [];
        $withoutSecurity = [];

        foreach ($resources as $resourceClass) {
            $analysis = $this->analyzeResource($resourceClass);

            if ($analysis['hasNativeSecurity']) {
                $withNativeSecurity[$resourceClass] = $analysis;
            } elseif ($analysis['hasVoterSecurity']) {
                $withVoterSecurity[$resourceClass] = $analysis;
            } else {
                $withoutSecurity[] = $resourceClass;
            }
        }

        $io->section('📊 Summary');

        $io->table(
            ['Category', 'Count'],
            [
                ['Resources with native security expressions', count($withNativeSecurity)],
                ['Resources with voter-based security', count($withVoterSecurity)],
                ['Resources without security', count($withoutSecurity)],
                ['Total resources', count($resources)],
            ]
        );

        if ($withNativeSecurity !== []) {
            $io->section('⚠️  Resources Using Native Security (Migration Recommended)');

            foreach ($withNativeSecurity as $resourceClass => $analysis) {
                $io->writeln(sprintf('  • <comment>%s</comment>', $this->getShortClassName($resourceClass)));

                if (! empty($analysis['securityExpressions'])) {
                    $io->writeln('    Security expressions found:');
                    foreach ($analysis['securityExpressions'] as $operation => $expression) {
                        $io->writeln(sprintf('      - %s: <info>%s</info>', $operation, $expression));
                    }
                }

                $io->newLine();
            }

            $complexity = $this->calculateMigrationComplexity($withNativeSecurity);
            $estimatedTime = $this->estimateMigrationTime(count($withNativeSecurity));

            $io->writeln([
                sprintf('Migration complexity: <comment>%s</comment>', strtoupper($complexity)),
                sprintf('Estimated time: <comment>%s</comment>', $estimatedTime),
            ]);
            $io->newLine();

            if ($io->confirm('Generate migration plan?', true)) {
                $this->generateMigrationPlan($io, $withNativeSecurity);
            }
        }

        if ($withVoterSecurity !== []) {
            $io->section('✅ Resources Already Using Voter Security');
            foreach ($withVoterSecurity as $resourceClass => $analysis) {
                $io->writeln(sprintf('  • <info>%s</info>', $this->getShortClassName($resourceClass)));
            }
            $io->newLine();
        }

        if ($withoutSecurity !== []) {
            $io->section('⚠️  Resources Without Security');
            $io->warning('The following resources have no security configuration:');
            foreach ($withoutSecurity as $resourceClass) {
                $io->writeln(sprintf('  • %s', $this->getShortClassName($resourceClass)));
            }
        }

        $io->success('Migration analysis completed!');

        return Command::SUCCESS;
    }

    private function findApiResources(): array
    {
        $resources = [];
        $srcDir = $this->projectDir . '/src';

        if (! is_dir($srcDir)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($srcDir)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }

            // Simple check for ApiResource attribute
            if (str_contains($content, '#[ApiResource') || str_contains($content, 'ApiResource(')) {
                $class = $this->extractClassName($file->getPathname(), $content);
                if ($class !== null && class_exists($class)) {
                    $resources[] = $class;
                }
            }
        }

        return $resources;
    }

    private function analyzeResource(string $resourceClass): array
    {
        $analysis = [
            'hasNativeSecurity' => false,
            'hasVoterSecurity' => false,
            'securityExpressions' => [],
        ];

        try {
            $reflection = new \ReflectionClass($resourceClass);
            $attributes = $reflection->getAttributes();

            foreach ($attributes as $attribute) {
                if ($attribute->getName() === \Nexara\ApiPlatformVoter\Attribute\Secured::class) {
                    $analysis['hasVoterSecurity'] = true;
                }
            }

            $collection = $this->resourceMetadataFactory->create($resourceClass);

            foreach ($collection as $resourceMetadata) {
                if (method_exists($resourceMetadata, 'getOperations')) {
                    foreach ($resourceMetadata->getOperations() as $operation) {
                        $security = method_exists($operation, 'getSecurity') ? $operation->getSecurity() : null;

                        if ($security !== null && is_string($security)) {
                            $analysis['hasNativeSecurity'] = true;
                            $operationName = method_exists($operation, 'getName') ? $operation->getName() : 'unknown';
                            $analysis['securityExpressions'][$operationName] = $security;
                        }
                    }
                }
            }
        } catch (\Throwable) {
            // Skip resources that can't be analyzed
        }

        return $analysis;
    }

    private function calculateMigrationComplexity(array $resources): string
    {
        $totalExpressions = 0;

        foreach ($resources as $analysis) {
            $totalExpressions += count($analysis['securityExpressions'] ?? []);
        }

        if ($totalExpressions <= 5) {
            return 'low';
        }

        if ($totalExpressions <= 15) {
            return 'medium';
        }

        return 'high';
    }

    private function estimateMigrationTime(int $resourceCount): string
    {
        $minutesPerResource = 30;
        $totalMinutes = $resourceCount * $minutesPerResource;

        if ($totalMinutes < 60) {
            return sprintf('%d minutes', $totalMinutes);
        }

        $hours = round($totalMinutes / 60, 1);
        return sprintf('%.1f hours', $hours);
    }

    private function generateMigrationPlan(SymfonyStyle $io, array $resources): void
    {
        $io->section('📋 Migration Plan');

        $step = 1;
        foreach ($resources as $resourceClass => $analysis) {
            $io->writeln(sprintf('<comment>Step %d: Migrate %s</comment>', $step, $this->getShortClassName($resourceClass)));
            $io->writeln('  1. Create voter class:');
            $io->writeln(sprintf('     <info>php bin/console make:api-resource-voter</info>'));
            $io->writeln('  2. Add #[Secured] attribute to resource');
            $io->writeln('  3. Implement voter methods based on security expressions');
            $io->writeln('  4. Remove native security expressions from operations');
            $io->writeln('  5. Test authorization logic');
            $io->newLine();
            $step++;
        }
    }

    private function extractClassName(string $filePath, string $content): ?string
    {
        // Extract namespace
        if (! preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatches)) {
            return null;
        }

        // Extract class name
        if (! preg_match('/class\s+(\w+)/', $content, $classMatches)) {
            return null;
        }

        return $namespaceMatches[1] . '\\' . $classMatches[1];
    }

    private function getShortClassName(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);
        return end($parts);
    }
}
