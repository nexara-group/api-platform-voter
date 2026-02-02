<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Cache;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;

final class OperationAttributeMapBuilder
{
    public function __construct(
        private readonly ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory,
    ) {
    }

    public function build(array $resourceClasses): array
    {
        $map = [];

        foreach ($resourceClasses as $resourceClass) {
            if (! class_exists($resourceClass)) {
                continue;
            }

            $shortName = $this->getShortClassName($resourceClass);
            $prefix = strtolower($shortName);

            try {
                $collection = $this->resourceMetadataFactory->create($resourceClass);

                foreach ($collection as $resourceMetadata) {
                    if (! method_exists($resourceMetadata, 'getOperations')) {
                        continue;
                    }

                    $operations = $resourceMetadata->getOperations();
                    if (! is_iterable($operations)) {
                        continue;
                    }

                    foreach ($operations as $operation) {
                        if (! $operation instanceof Operation) {
                            continue;
                        }

                        $key = $this->buildKey($operation, $resourceClass);
                        $attribute = $this->mapOperationToAttribute($operation, $prefix);

                        if ($key !== null && $attribute !== null) {
                            $map[$key] = $attribute;
                        }
                    }
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return $map;
    }

    public function warmUp(string $cacheDir, array $resourceClasses, ?string $buildDir = null): string
    {
        $map = $this->build($resourceClasses);
        $targetDir = $buildDir ?? $cacheDir;
        $filePath = $targetDir . '/nexara_voter_operation_map.php';

        file_put_contents(
            $filePath,
            '<?php return ' . var_export($map, true) . ';'
        );

        return $filePath;
    }

    private function buildKey(Operation $operation, string $resourceClass): ?string
    {
        $operationClass = $operation::class;
        $operationName = $operation->getName();

        if (is_string($operationName) && $operationName !== '') {
            return sprintf('%s:%s:%s', $operationClass, $resourceClass, $operationName);
        }

        return sprintf('%s:%s', $operationClass, $resourceClass);
    }

    private function mapOperationToAttribute(Operation $operation, string $prefix): ?string
    {
        $operationKey = $operation->getName();
        if (is_string($operationKey) && $operationKey !== '') {
            if (! str_starts_with($operationKey, '_api_')) {
                return $prefix . ':' . $operationKey;
            }
        }

        if ($operation instanceof GetCollection) {
            return $prefix . ':list';
        }

        if ($operation instanceof Get) {
            return $prefix . ':read';
        }

        if ($operation instanceof Post) {
            return $prefix . ':create';
        }

        if ($operation instanceof Put || $operation instanceof Patch) {
            return $prefix . ':update';
        }

        if ($operation instanceof Delete) {
            return $prefix . ':delete';
        }

        return null;
    }

    private function getShortClassName(string $fqcn): string
    {
        $parts = explode('\\', ltrim($fqcn, '\\'));

        return end($parts) ?: $fqcn;
    }
}
