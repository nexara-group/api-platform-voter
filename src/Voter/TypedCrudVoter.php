<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Voter;

/**
 * Type-safe CRUD voter with generic type support.
 *
 * @deprecated Since 0.3.0, use CrudVoter with @template PHPDoc instead.
 *
 * @example Migration:
 * ```php
 * // OLD:
 * /** @extends TypedCrudVoter<Article> *\/
 * final class ArticleVoter extends TypedCrudVoter
 * {
 *     abstract protected function canRead(mixed $object): bool;
 * }
 *
 * // NEW:
 * /** @template T of Article *\/
 * final class ArticleVoter extends CrudVoter
 * {
 *     protected function canRead(mixed $object): bool
 *     {
 *         // PHPStan knows $object is Article
 *         return $object->isPublished();
 *     }
 * }
 * ```
 *
 * @template T of object
 */
abstract class TypedCrudVoter extends CrudVoter
{
    /**
     * @param T $object
     */
    abstract protected function canRead(mixed $object): bool;

    /**
     * @param T $object
     * @param T|null $previousObject
     */
    abstract protected function canUpdate(mixed $object, mixed $previousObject): bool;

    /**
     * @param T $object
     */
    abstract protected function canDelete(mixed $object): bool;

    /**
     * @param T $object
     */
    protected function canCreate(mixed $object): bool
    {
        return true;
    }

    /**
     * @param T $object
     * @param T|null $previousObject
     */
    protected function canCustomOperation(string $operation, mixed $object, mixed $previousObject): bool
    {
        return false;
    }
}
