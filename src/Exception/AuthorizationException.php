<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Exception;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AuthorizationException extends AccessDeniedException
{
    public function __construct(
        string $message = '',
        private readonly ?string $errorCode = null,
        private readonly array $context = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $previous);
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public static function resourceAccessDenied(
        string $attribute,
        string $resourceClass,
        string $operationClass,
        array $context = []
    ): self {
        return new self(
            message: sprintf(
                'Access denied for attribute "%s" on resource "%s" (operation "%s").',
                $attribute,
                $resourceClass,
                $operationClass
            ),
            errorCode: 'RESOURCE_ACCESS_DENIED',
            context: array_merge($context, [
                'attribute' => $attribute,
                'resource_class' => $resourceClass,
                'operation_class' => $operationClass,
            ])
        );
    }

    public static function operationNotAllowed(
        string $operation,
        string $resourceClass,
        ?string $reason = null
    ): self {
        $message = sprintf(
            'Operation "%s" is not allowed on resource "%s".',
            $operation,
            $resourceClass
        );

        if ($reason !== null) {
            $message .= ' Reason: ' . $reason;
        }

        return new self(
            message: $message,
            errorCode: 'OPERATION_NOT_ALLOWED',
            context: [
                'operation' => $operation,
                'resource_class' => $resourceClass,
                'reason' => $reason,
            ]
        );
    }

    public static function insufficientRole(
        string $attribute,
        string $requiredRole,
        array $userRoles = [],
        ?string $suggestion = null
    ): self {
        $message = sprintf(
            'Insufficient permissions for "%s". Required role: %s',
            $attribute,
            $requiredRole
        );

        return new self(
            message: $message,
            errorCode: 'INSUFFICIENT_ROLE',
            context: [
                'attribute' => $attribute,
                'required_role' => $requiredRole,
                'user_roles' => $userRoles,
                'suggestion' => $suggestion ?? sprintf('You need %s role to perform this action.', $requiredRole),
            ]
        );
    }

    public static function notOwner(
        string $attribute,
        string $resourceClass,
        mixed $resourceId = null,
        ?string $userId = null
    ): self {
        $message = sprintf(
            'You are not the owner of this %s and cannot perform "%s" operation.',
            $resourceClass,
            $attribute
        );

        return new self(
            message: $message,
            errorCode: 'NOT_OWNER',
            context: [
                'attribute' => $attribute,
                'resource_class' => $resourceClass,
                'resource_id' => $resourceId,
                'user_id' => $userId,
                'suggestion' => 'Only the resource owner can perform this action.',
            ]
        );
    }

    public static function contextRestriction(
        string $attribute,
        string $restriction,
        array $context = []
    ): self {
        $message = sprintf(
            'Access denied for "%s" due to context restriction: %s',
            $attribute,
            $restriction
        );

        return new self(
            message: $message,
            errorCode: 'CONTEXT_RESTRICTION',
            context: array_merge([
                'attribute' => $attribute,
                'restriction' => $restriction,
            ], $context)
        );
    }

    public function toArray(): array
    {
        return [
            'error' => $this->errorCode,
            'message' => $this->getMessage(),
            'context' => $this->context,
        ];
    }

    public function getSuggestion(): ?string
    {
        return $this->context['suggestion'] ?? null;
    }
}
