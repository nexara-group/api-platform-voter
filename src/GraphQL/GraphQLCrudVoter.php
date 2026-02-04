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

    /**
     * Field-level authorization for GraphQL.
     *
     * Override this method to control access to specific fields.
     *
     * @param string $fieldName The field being accessed
     * @param mixed $object The parent object
     * @return bool True if access is granted, false otherwise
     *
     * @example
     * ```php
     * protected function canAccessField(string $fieldName, mixed $object): bool
     * {
     *     return match ($fieldName) {
     *         'email' => $this->security->isGranted('ROLE_ADMIN'),
     *         'internalNotes' => $object->getAuthor() === $this->security->getUser(),
     *         default => true,
     *     };
     * }
     * ```
     */
    protected function canAccessField(string $fieldName, mixed $object): bool
    {
        // By default, all fields are accessible
        // Override in child classes for field-level security
        return true;
    }

    /**
     * Checks if a specific field can be modified via GraphQL mutation.
     *
     * @param string $fieldName The field being modified
     * @param mixed $object The object being modified
     * @param mixed $newValue The new value being set
     * @return bool True if modification is allowed
     */
    protected function canModifyField(string $fieldName, mixed $object, mixed $newValue): bool
    {
        // By default, allow modification if update is allowed
        return true;
    }

    private function normalizeSubject(mixed $subject): array
    {
        if (is_array($subject)) {
            return [$subject[0] ?? null, $subject[1] ?? null];
        }

        return [$subject, null];
    }
}
