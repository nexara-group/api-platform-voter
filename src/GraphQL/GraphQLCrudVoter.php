<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\GraphQL;

use Nexara\ApiPlatformVoter\Voter\CrudVoter;

/**
 * Extended CrudVoter with GraphQL support
 */
abstract class GraphQLCrudVoter extends CrudVoter implements GraphQLVoterInterface
{
    public function supportsGraphQLQuery(string $queryName, mixed $subject): bool
    {
        return $this->supportsSubject($subject);
    }

    public function supportsGraphQLMutation(string $mutationName, mixed $subject): bool
    {
        return $this->supportsSubject($subject);
    }

    public function voteOnGraphQLQuery(string $queryName, mixed $subject): bool
    {
        // GraphQL queries are treated as read operations
        return $this->canRead($subject);
    }

    public function voteOnGraphQLMutation(string $mutationName, mixed $subject): bool
    {
        // Map mutations to CRUD operations
        if (str_starts_with($mutationName, 'create')) {
            return $this->canCreate($subject);
        }

        if (str_starts_with($mutationName, 'update')) {
            [$object, $previousObject] = $this->normalizeSubject($subject);
            return $this->canUpdate($object, $previousObject);
        }

        if (str_starts_with($mutationName, 'delete')) {
            return $this->canDelete($subject);
        }

        // Custom mutations
        return $this->canCustomOperation($mutationName, $subject, null);
    }

    private function normalizeSubject(mixed $subject): array
    {
        if (is_array($subject)) {
            return [$subject[0] ?? null, $subject[1] ?? null];
        }

        return [$subject, null];
    }
}
