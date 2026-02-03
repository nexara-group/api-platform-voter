<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\GraphQL;

final class GraphQLOperationMapper
{
    public function mapQueryToAttribute(string $queryName, string $prefix): string
    {
        // GraphQL queries map to read operations
        return $prefix . ':read';
    }

    public function mapMutationToAttribute(string $mutationName, string $prefix): string
    {
        // Detect CRUD operations from mutation names
        if (str_starts_with($mutationName, 'create')) {
            return $prefix . ':create';
        }

        if (str_starts_with($mutationName, 'update')) {
            return $prefix . ':update';
        }

        if (str_starts_with($mutationName, 'delete')) {
            return $prefix . ':delete';
        }

        // Custom mutations use the mutation name
        return $prefix . ':' . $mutationName;
    }
}
