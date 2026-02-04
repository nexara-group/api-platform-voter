<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\OperationToVoterAttributeMapperInterface;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\SubjectResolverInterface;
use Nexara\ApiPlatformVoter\Metadata\ResourceAccessMetadataResolverInterface;
use Nexara\ApiPlatformVoter\Voter\TargetVoterSubject;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Decorator for custom providers to add security checks.
 *
 * @internal
 * @implements ProviderInterface<object|array|null>
 */
final class CustomProviderDecorator implements ProviderInterface
{
    /**
     * @param ProviderInterface<object|array|null> $decorated
     */
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
            throw new AccessDeniedException(sprintf(
                'Access denied for attribute "%s" on resource "%s" (operation "%s").',
                $attribute,
                $resourceClass,
                $operation::class,
            ));
        }

        return $data;
    }
}
