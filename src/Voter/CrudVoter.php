<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Voter;

use LogicException;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Base voter for API Platform CRUD operations.
 *
 * Provides automatic mapping of CRUD operations to voter methods:
 * - GET collection -> canList()
 * - POST collection -> canCreate($object)
 * - GET item -> canRead($object)
 * - PUT/PATCH item -> canUpdate($object, $previousObject)
 * - DELETE item -> canDelete($object)
 * - Custom operations -> canCustomOperation($operation, $object, $previousObject)
 *
 * Example usage:
 * ```php
 * final class ArticleVoter extends CrudVoter
 * {
 *     public function __construct(private readonly Security $security)
 *     {
 *         $this->setPrefix('article');
 *         $this->setResourceClasses(Article::class);
 *     }
 *
 *     protected function canRead(mixed $object): bool
 *     {
 *         return true; // Everyone can read
 *     }
 *
 *     protected function canUpdate(mixed $object, mixed $previousObject): bool
 *     {
 *         return $object->getAuthor() === $this->security->getUser();
 *     }
 * }
 * ```
 */
abstract class CrudVoter extends Voter
{
    protected const LIST = 'list';

    protected const CREATE = 'create';

    protected const READ = 'read';

    protected const UPDATE = 'update';

    protected const DELETE = 'delete';

    protected string $prefix;

    /** @var array<int, class-string> */
    protected array $resourceClasses = [];

    /** @var array<int, string> */
    protected array $customOperations = [];

    protected function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * @param array<int, class-string>|class-string $resourceClasses
     */
    protected function setResourceClasses(array|string $resourceClasses): void
    {
        $this->resourceClasses = is_array($resourceClasses) ? $resourceClasses : [$resourceClasses];

        foreach ($this->resourceClasses as $class) {
            if (! class_exists($class)) {
                throw new LogicException("Class '{$class}' not found.");
            }
        }

        if (! isset($this->prefix) && $this->resourceClasses !== []) {
            $ref = new ReflectionClass($this->resourceClasses[0]);
            $this->prefix = strtolower($ref->getShortName());
        }
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($subject instanceof TargetVoterSubject) {
            if ($subject->voterClass !== static::class) {
                return false;
            }
            $subject = $subject->subject;
        }

        if (! isset($this->prefix)) {
            $this->initializePrefix();
        }

        if (! str_starts_with($attribute, $this->prefix . ':')) {
            return false;
        }

        $operation = substr($attribute, strlen($this->prefix) + 1);

        if (in_array($operation, [self::LIST, self::CREATE, self::READ, self::UPDATE, self::DELETE], true)) {
            return $this->supportsSubject($subject);
        }

        if (in_array($operation, $this->customOperations, true)) {
            return $this->supportsSubject($subject);
        }

        return false;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if ($subject instanceof TargetVoterSubject) {
            if ($subject->voterClass !== static::class) {
                return false;
            }
            $subject = $subject->subject;
        }

        $operation = substr($attribute, strlen($this->prefix) + 1);

        [$object, $previousObject] = $this->normalizeSubject($subject);

        return match ($operation) {
            self::LIST => $this->canList(),
            self::CREATE => $this->canCreate($object),
            self::READ => $this->canRead($object),
            self::UPDATE => $this->canUpdate($object, $previousObject),
            self::DELETE => $this->canDelete($object),
            default => $this->canCustomOperation($operation, $object, $previousObject),
        };
    }

    protected function canList(): bool
    {
        return true;
    }

    protected function canCreate(mixed $object): bool
    {
        return true;
    }

    protected function canRead(mixed $object): bool
    {
        return true;
    }

    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        return true;
    }

    protected function canDelete(mixed $object): bool
    {
        return true;
    }

    protected function canCustomOperation(string $operation, mixed $object, mixed $previousObject): bool
    {
        return false;
    }

    protected function supportsSubject(mixed $subject): bool
    {
        if ($this->resourceClasses === []) {
            throw new LogicException('Set resource classes via setResourceClasses() in constructor.');
        }

        $object = $this->extractMainObject($subject);

        foreach ($this->resourceClasses as $resourceClass) {
            if (is_string($object) && class_exists($object) && is_a($object, $resourceClass, true)) {
                return true;
            }

            if (is_object($object) && $object instanceof $resourceClass) {
                return true;
            }
        }

        return false;
    }

    private function extractMainObject(mixed $subject): mixed
    {
        if ($subject instanceof TargetVoterSubject) {
            $subject = $subject->subject;
        }

        if (is_array($subject) && isset($subject[0])) {
            return $subject[0];
        }

        if ($subject instanceof Request) {
            return $subject->get('data') ?? $subject->get('resource_class');
        }

        return $subject;
    }

    /**
     * @return array{mixed, mixed|null}
     */
    private function normalizeSubject(mixed $subject): array
    {
        if ($subject instanceof TargetVoterSubject) {
            $subject = $subject->subject;
        }

        if (is_array($subject)) {
            return [$subject[0] ?? null, $subject[1] ?? null];
        }

        return [$subject, null];
    }

    private function initializePrefix(): void
    {
        if ($this->resourceClasses === []) {
            throw new LogicException('Set resource classes via setResourceClasses() in constructor.');
        }

        $ref = new ReflectionClass($this->resourceClasses[0]);
        $this->prefix = strtolower($ref->getShortName());
    }
}
