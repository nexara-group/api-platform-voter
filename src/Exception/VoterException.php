<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Exception;

use RuntimeException;

class VoterException extends RuntimeException
{
    public static function missingPrefix(string $voterClass): self
    {
        return new self(sprintf(
            'Voter "%s" must define a prefix via setPrefix() in constructor or use AutoConfiguredCrudVoter.',
            $voterClass
        ));
    }

    public static function missingResourceClasses(string $voterClass): self
    {
        return new self(sprintf(
            'Voter "%s" must define resource classes via setResourceClasses() in constructor.',
            $voterClass
        ));
    }

    public static function invalidResourceClass(string $class): self
    {
        return new self(sprintf(
            'Resource class "%s" does not exist.',
            $class
        ));
    }

    public static function customOperationNotSupported(string $operation, string $voterClass): self
    {
        return new self(sprintf(
            'Custom operation "%s" is not supported by voter "%s". Add it to $customOperations array or implement can%s() method.',
            $operation,
            $voterClass,
            ucfirst($operation)
        ));
    }

    public static function invalidConfiguration(string $message): self
    {
        return new self(sprintf(
            'Invalid voter configuration: %s',
            $message
        ));
    }
}
