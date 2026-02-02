<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Maker\Util;

use Nexara\ApiPlatformVoter\Attribute\Secured;
use ReflectionClass;

final class PhpResourceVoterAttributeAdder
{
    public function addToResourceClass(string $resourceClass, string $voterFqcn, ?string $prefix): void
    {
        if (! class_exists($resourceClass)) {
            throw new \RuntimeException("Resource class '{$resourceClass}' was not found.");
        }

        $ref = new ReflectionClass($resourceClass);
        $file = $ref->getFileName();
        if (! is_string($file) || ! is_file($file)) {
            throw new \RuntimeException("Cannot locate file for resource class '{$resourceClass}'.");
        }

        $code = file_get_contents($file);
        if (! is_string($code) || $code === '') {
            throw new \RuntimeException("Cannot read file '{$file}'.");
        }

        // Check if attribute already exists
        if ($this->hasSecuredAttribute($code)) {
            return;
        }

        // Add use statements if needed
        $code = $this->addUseStatements($code, $voterFqcn);

        // Add attribute to class
        $code = $this->addAttributeToClass($code, $resourceClass, $voterFqcn, $prefix);

        file_put_contents($file, $code);
    }

    private function hasSecuredAttribute(string $code): bool
    {
        return preg_match('/#\[Secured\s*\(/i', $code) === 1;
    }

    private function addUseStatements(string $code, string $voterFqcn): string
    {
        $securedUse = 'use ' . Secured::class . ';';
        $voterUse = 'use ' . $voterFqcn . ';';

        // Check if Secured use already exists
        if (! str_contains($code, $securedUse)) {
            // Find the last use statement
            if (preg_match('/^use\s+[^;]+;/m', $code, $matches, PREG_OFFSET_CAPTURE)) {
                $lastUsePos = $matches[0][1] + strlen($matches[0][0]);
                $code = substr_replace($code, "\n" . $securedUse, $lastUsePos, 0);
            }
        }

        // Check if Voter use already exists
        if (! str_contains($code, $voterUse)) {
            // Find the last use statement
            if (preg_match_all('/^use\s+[^;]+;/m', $code, $matches, PREG_OFFSET_CAPTURE)) {
                $lastMatch = end($matches[0]);
                if ($lastMatch !== false) {
                    $lastUsePos = $lastMatch[1] + strlen($lastMatch[0]);
                    $code = substr_replace($code, "\n" . $voterUse, $lastUsePos, 0);
                }
            }
        }

        return $code;
    }

    private function addAttributeToClass(string $code, string $resourceClass, string $voterFqcn, ?string $prefix): string
    {
        $shortClassName = $this->getShortClassName($resourceClass);
        $voterShortName = $this->getShortClassName($voterFqcn);

        // Build attribute string
        if ($prefix !== null && $prefix !== '') {
            $attributeString = "#[Secured(voter: {$voterShortName}::class, prefix: '{$prefix}')]";
        } else {
            $attributeString = "#[Secured(voter: {$voterShortName}::class)]";
        }

        // Find the class declaration and add attribute before it
        // Look for pattern: (optional attributes) class ClassName
        $pattern = '/((?:#\[[^\]]+\]\s*)*)(class\s+' . preg_quote($shortClassName, '/') . '\s)/m';

        if (preg_match($pattern, $code, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPos = $matches[1][1] + strlen($matches[1][0]);
            $code = substr_replace($code, $attributeString . "\n", $insertPos, 0);
        }

        return $code;
    }

    private function getShortClassName(string $fqcn): string
    {
        $parts = explode('\\', ltrim($fqcn, '\\'));
        return end($parts) ?: $fqcn;
    }
}
