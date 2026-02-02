<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\GraphQL;

interface GraphQLVoterInterface
{
    public function canGraphQLQuery(string $fieldName, mixed $object, array $args = []): bool;

    public function canGraphQLMutation(string $fieldName, mixed $object, array $args = []): bool;

    public function canGraphQLSubscription(string $fieldName, mixed $object, array $args = []): bool;
}
