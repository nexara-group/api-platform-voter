<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\GraphQL;

interface GraphQLVoterInterface
{
    public function supportsGraphQLQuery(string $queryName, mixed $subject): bool;

    public function supportsGraphQLMutation(string $mutationName, mixed $subject): bool;

    public function voteOnGraphQLQuery(string $queryName, mixed $subject): bool;

    public function voteOnGraphQLMutation(string $mutationName, mixed $subject): bool;
}
