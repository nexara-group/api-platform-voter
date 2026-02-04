<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Exception;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class NoVoterFoundException extends AccessDeniedException
{
    public function __construct(string $attribute, mixed $subject)
    {
        $subjectType = get_debug_type($subject);

        parent::__construct(
            sprintf(
                'No voter found to handle attribute "%s" for subject of type "%s". ' .
                'Enable strict_mode: false in configuration to allow silent denial.',
                $attribute,
                $subjectType
            )
        );
    }
}
