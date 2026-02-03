<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Cache;

use Nexara\ApiPlatformVoter\Attribute\Secured;
use Nexara\ApiPlatformVoter\Util\PhpClassParser;
use ReflectionClass;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

final class VoterMetadataWarmer implements CacheWarmerInterface
{
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function isOptional(): bool
    {
        return false;
    }

    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $metadata = [];
        $resources = $this->findAllApiResources();

        foreach ($resources as $resourceClass) {
            $metadata[$resourceClass] = $this->extractMetadata($resourceClass);
        }

        $targetDir = $buildDir ?? $cacheDir;
        $filePath = $targetDir . '/nexara_voter_metadata.php';

        file_put_contents(
            $filePath,
            '<?php return ' . var_export($metadata, true) . ';'
        );

        return [$filePath];
    }

    private function findAllApiResources(): array
    {
        $resources = [];
        $srcDir = $this->projectDir . '/src';

        if (! is_dir($srcDir)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($srcDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $parser = new PhpClassParser();

        foreach ($iterator as $file) {
            if (! $file instanceof \SplFileInfo || $file->getExtension() !== 'php') {
                continue;
            }

            // Skip vendor, tests, and var directories
            $path = $file->getPathname();
            if (str_contains($path, '/vendor/') || str_contains($path, '/tests/') || str_contains($path, '/var/')) {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }

            if (! str_contains($content, 'Secured')) {
                continue;
            }

            $classInfo = $parser->extractClassInfo($file->getPathname());
            if ($classInfo === null) {
                continue;
            }

            $className = $classInfo['fqcn'];

            if (class_exists($className)) {
                $resources[] = $className;
            }
        }

        return $resources;
    }

    private function extractMetadata(string $resourceClass): array
    {
        if (! class_exists($resourceClass)) {
            return [
                'protected' => false,
                'prefix' => null,
                'voter' => null,
            ];
        }

        $ref = new ReflectionClass($resourceClass);
        $attrs = $ref->getAttributes(Secured::class);

        if ($attrs === []) {
            return [
                'protected' => false,
                'prefix' => null,
                'voter' => null,
            ];
        }

        $cfg = $attrs[0]->newInstance();
        $prefix = $cfg->prefix;

        if (! is_string($prefix) || $prefix === '') {
            $prefix = strtolower($ref->getShortName());
        }

        return [
            'protected' => true,
            'prefix' => $prefix,
            'voter' => $cfg->voter,
        ];
    }
}
