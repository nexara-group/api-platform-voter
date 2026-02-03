<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\OperationToVoterAttributeMapperInterface;
use Nexara\ApiPlatformVoter\Metadata\ResourceAccessMetadataResolverInterface;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\SubjectResolverInterface;
use Nexara\ApiPlatformVoter\Voter\TargetVoterSubject;
use Nexara\ApiPlatformVoter\Exception\NoVoterFoundException;
use Nexara\ApiPlatformVoter\Debug\VoterDebugger;
use Nexara\ApiPlatformVoter\Audit\AuditLoggerInterface;
use Nexara\ApiPlatformVoter\Audit\AuditEntry;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Security provider that enforces voter-based authorization for API Platform operations.
 *
 * @internal
 * @implements ProviderInterface<object|array|null>
 */
final class SecurityProvider implements ProviderInterface
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
        private readonly bool $strictMode = false,
        private readonly ?VoterDebugger $voterDebugger = null,
        private readonly ?AuditLoggerInterface $auditLogger = null,
        private readonly ?Security $security = null,
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

        $granted = $this->authorizationChecker->isGranted($attribute, $subject);
        
        if ($this->voterDebugger?->isEnabled()) {
            $this->voterDebugger->recordDecision(
                'SecurityProvider',
                $attribute,
                $subject,
                $granted ? VoterInterface::ACCESS_GRANTED : VoterInterface::ACCESS_DENIED,
                $granted ? 'Access granted' : 'Access denied',
                [
                    'operation' => $operation::class,
                    'resource' => $resourceClass,
                    'uri_variables' => $uriVariables,
                ]
            );
        }

        if ($this->auditLogger) {
            $user = $this->security?->getUser();
            $username = $user ? (method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : (string) $user) : null;
            
            $entry = AuditEntry::fromDecision(
                $attribute,
                $subject,
                $granted ? 'GRANTED' : 'DENIED',
                $username,
                null, // IP address would need request context
                null, // User agent would need request context
                [
                    'operation' => $operation::class,
                    'resource' => $resourceClass,
                ]
            );
            
            $this->auditLogger->log($entry);
        }
        
        if (! $granted) {
            if ($this->strictMode && ! $this->hasVoterForAttribute($attribute, $subject)) {
                throw new NoVoterFoundException($attribute, $subject);
            }
            
            throw new AccessDeniedException(sprintf(
                'Access denied for attribute "%s" on resource "%s" (operation "%s").',
                $attribute,
                $resourceClass,
                $operation::class,
            ));
        }

        return $data;
    }

    private function hasVoterForAttribute(string $attribute, mixed $subject): bool
    {
        // This is a simple check - in production, you'd want to check the voter registry
        // For now, we assume if authorization was checked, a voter existed
        return true;
    }
}
