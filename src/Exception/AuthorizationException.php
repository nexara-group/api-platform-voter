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
}
