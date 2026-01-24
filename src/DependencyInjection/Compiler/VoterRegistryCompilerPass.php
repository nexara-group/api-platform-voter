<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\DependencyInjection\Compiler;

use Nexara\ApiPlatformVoter\Attribute\ApiResourceVoter;
use Nexara\ApiPlatformVoter\Security\VoterRegistry;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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

    private function scanDirectory(string $dir, $registry): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $this->processFile($file->getPathname(), $registry);
        }
    }

    private function processFile(string $filePath, $registry): void
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return;
        }

        if (! preg_match('/namespace\s+([^;]+);/', $content, $nsMatch)) {
            return;
        }

        if (! preg_match('/class\s+(\w+)/', $content, $classMatch)) {
            return;
        }

        $className = $nsMatch[1] . '\\' . $classMatch[1];

        if (! class_exists($className)) {
            return;
        }

        try {
            $reflection = new ReflectionClass($className);
        } catch (\ReflectionException $e) {
            return;
        }

        $attributes = $reflection->getAttributes(ApiResourceVoter::class);

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
