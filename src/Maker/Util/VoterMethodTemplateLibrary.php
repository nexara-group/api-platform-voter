<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Maker\Util;

/**
 * Library of common voter method implementation templates.
 *
 * Provides pre-defined patterns for authorization logic.
 */
final class VoterMethodTemplateLibrary
{
    /**
     * @return array<string, array{description: string, code: string}>
     */
    public static function getTemplates(): array
    {
        return [
            'everyone' => [
                'description' => 'Allow everyone (public access)',
                'code' => 'return true;',
            ],
            'authenticated_only' => [
                'description' => 'Only authenticated users',
                'code' => 'return $this->security->getUser() !== null;',
            ],
            'role_based' => [
                'description' => 'Role-based authorization',
                'code' => 'return $this->security->isGranted(\'ROLE_USER\'); // Change role as needed',
            ],
            'owner_only' => [
                'description' => 'Only the resource owner',
                'code' => 'return $object->getAuthor() === $this->security->getUser(); // Adjust property name',
            ],
            'owner_or_admin' => [
                'description' => 'Owner or admin',
                'code' => <<<'PHP'
return $object->getAuthor() === $this->security->getUser()
            || $this->security->isGranted('ROLE_ADMIN');
PHP,
            ],
            'status_based' => [
                'description' => 'Based on resource status',
                'code' => <<<'PHP'
// Example: Only allow if status is 'draft'
        return $object->getStatus() === 'draft';
PHP,
            ],
            'time_based' => [
                'description' => 'Time-based authorization',
                'code' => <<<'PHP'
// Example: Only allow before publication date
        $now = new \DateTimeImmutable();
        return $object->getPublishedAt() === null 
            || $object->getPublishedAt() > $now;
PHP,
            ],
            'multi_condition' => [
                'description' => 'Multiple conditions',
                'code' => <<<'PHP'
// Combine multiple checks
        $user = $this->security->getUser();
        
        if ($user === null) {
            return false;
        }
        
        // User must be owner AND resource must be draft
        return $object->getAuthor() === $user 
            && $object->getStatus() === 'draft';
PHP,
            ],
            'prevent_author_change' => [
                'description' => 'Prevent changing specific fields (UPDATE)',
                'code' => <<<'PHP'
// Example for UPDATE: Prevent changing author
        if ($object->getAuthor() !== $previousObject->getAuthor()) {
            return $this->security->isGranted('ROLE_ADMIN');
        }
        
        return $object->getAuthor() === $this->security->getUser();
PHP,
            ],
            'hierarchical_roles' => [
                'description' => 'Role hierarchy (Moderator > User)',
                'code' => <<<'PHP'
if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }
        
        if ($this->security->isGranted('ROLE_MODERATOR')) {
            // Moderators can only modify their own
            return $object->getAuthor() === $this->security->getUser();
        }
        
        return false;
PHP,
            ],
        ];
    }

    /**
     * @return array<string>
     */
    public static function getTemplateNames(): array
    {
        return array_keys(self::getTemplates());
    }

    public static function getTemplate(string $name): ?array
    {
        $templates = self::getTemplates();
        return $templates[$name] ?? null;
    }

    public static function formatTemplateCode(string $code, int $indentLevel = 2): string
    {
        $indent = str_repeat('    ', $indentLevel);
        $lines = explode("\n", $code);

        return implode("\n", array_map(
            fn ($line) => $indent . $line,
            $lines
        ));
    }
}
