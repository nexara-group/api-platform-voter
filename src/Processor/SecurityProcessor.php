<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\OperationToVoterAttributeMapperInterface;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\SubjectResolverInterface;
use Nexara\ApiPlatformVoter\Audit\AuditEntry;
use Nexara\ApiPlatformVoter\Audit\AuditLoggerInterface;
use Nexara\ApiPlatformVoter\Debug\VoterDebugger;
use Nexara\ApiPlatformVoter\Exception\NoVoterFoundException;
use Nexara\ApiPlatformVoter\Metadata\ResourceAccessMetadataResolverInterface;
use Nexara\ApiPlatformVoter\Voter\TargetVoterSubject;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Security processor that enforces voter-based authorization for API Platform operations.
 *
 * @internal
 * @implements ProcessorInterface<mixed, mixed>
 */
final class SecurityProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<mixed, mixed> $decorated
     */
    public function __construct(
        private readonly ProcessorInterface $decorated,
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

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($this->enabled) {
            $resourceClass = $operation->getClass() ?? ($context['resource_class'] ?? null);
            if (is_string($resourceClass) && $resourceClass !== '') {
                $metadata = $this->metadataResolver->resolve($resourceClass);
                if ($metadata->protected && is_string($metadata->prefix) && $metadata->prefix !== '') {
                    $attribute = $this->mapper->map($operation, $metadata->prefix);
                    if ($attribute !== null) {
                        $subject = $this->subjectResolver->resolve($operation, $data, $context);
                        if (is_string($metadata->voter) && $metadata->voter !== '') {
                            $subject = new TargetVoterSubject($subject, $metadata->voter);
                        }

                        $granted = $this->authorizationChecker->isGranted($attribute, $subject);

                        if ($this->voterDebugger?->isEnabled()) {
                            $this->voterDebugger->recordDecision(
                                'SecurityProcessor',
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
                                null,
                                null,
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
                    }
                }
            }
        }

        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }

    private function hasVoterForAttribute(string $attribute, mixed $subject): bool
    {
        // This is a simple check - in production, you'd want to check the voter registry
        // For now, we assume if authorization was checked, a voter existed
        return true;
    }
}
