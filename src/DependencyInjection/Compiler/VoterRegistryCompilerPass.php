<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\DependencyInjection\Compiler;

use Nexara\ApiPlatformVoter\Attribute\Secured;
use Nexara\ApiPlatformVoter\Security\VoterRegistry;
use Nexara\ApiPlatformVoter\Util\PhpClassParser;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

final class VoterRegistryCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->has(VoterRegistry::class)) {
            return;
        }

        $registry = $container->findDefinition(VoterRegistry::class);

        $projectDir = $container->getParameter('kernel.project_dir');
        if (! is_string($projectDir)) {
            return;
        }

        $srcDir = $projectDir . '/src';

        if (! is_dir($srcDir)) {
            return;
        }

        $this->scanDirectory($srcDir, $registry);
    }

    private function scanDirectory(string $dir, Definition $registry): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo) {
                continue;
            }
            
            // Skip vendor, tests, and var directories for performance
            $path = $file->getPathname();
            if (str_contains($path, '/vendor/') || str_contains($path, '/tests/') || str_contains($path, '/var/')) {
                continue;
            }
            
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $this->processFile($file->getPathname(), $registry);
        }
    }

    private function processFile(string $filePath, Definition $registry): void
    {
        $parser = new PhpClassParser();
        $classInfo = $parser->extractClassInfo($filePath);

        if ($classInfo === null) {
            return;
        }

        $className = $classInfo['fqcn'];

        if (! class_exists($className)) {
            return;
        }

        $reflection = new ReflectionClass($className);

        $attributes = $reflection->getAttributes(Secured::class);

        if ($attributes === []) {
            return;
        }

        $attribute = $attributes[0]->newInstance();

        if ($attribute->voter && class_exists($attribute->voter)) {
            $registry->addMethodCall('register', [
                $attribute->voter,
                $className,
            ]);
        }
    }
}
