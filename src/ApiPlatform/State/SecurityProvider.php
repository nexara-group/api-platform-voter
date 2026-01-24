<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\OperationToVoterAttributeMapperInterface;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\ResourceAccessMetadataResolverInterface;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\SubjectResolverInterface;
use Nexara\ApiPlatformVoter\Security\Voter\TargetVoterSubject;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class SecurityProvider implements ProviderInterface
{
    public function __construct(
        private readonly ProviderInterface $decorated,
        private readonly OperationToVoterAttributeMapperInterface $mapper,
        private readonly ResourceAccessMetadataResolverInterface $metadataResolver,
        private readonly SubjectResolverInterface $subjectResolver,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly bool $enabled,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $data = $this->decorated->provide($operation, $uriVariables, $context);

        if (! $this->enabled) {
            return $data;
        }

        $resourceClass = $operation->getClass() ?? ($context['resource_class'] ?? null);
        if (! is_string($resourceClass) || $resourceClass === '') {
            return $data;
        }

        $metadata = $this->metadataResolver->resolve($resourceClass);
        if (! $metadata->protected || ! is_string($metadata->prefix) || $metadata->prefix === '') {
            return $data;
        }

        $attribute = $this->mapper->map($operation, $metadata->prefix);
        if ($attribute === null) {
            return $data;
        }

        $subject = $this->subjectResolver->resolve($operation, $data, $context);
        if (is_string($metadata->voter) && $metadata->voter !== '') {
            $subject = new TargetVoterSubject($subject, $metadata->voter);
        }

        if (! $this->authorizationChecker->isGranted($attribute, $subject)) {
            throw new AccessDeniedException();
        }

        return $data;
    }
}
