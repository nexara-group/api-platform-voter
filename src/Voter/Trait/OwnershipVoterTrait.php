<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Security\Voter\Trait;

use Symfony\Component\Security\Core\User\UserInterface;

trait OwnershipVoterTrait
{
    protected function isOwner(mixed $object, ?UserInterface $user = null): bool
    {
        if ($user === null) {
            return false;
        }

        if (! method_exists($object, 'getOwner') && ! method_exists($object, 'getAuthor')) {
            return false;
        }

        $owner = method_exists($object, 'getOwner') ? $object->getOwner() : $object->getAuthor();

        if ($owner === null) {
            return false;
        }

        if (is_object($owner)) {
            return $owner === $user;
        }

        if (method_exists($user, 'getId')) {
            return $owner === $user->getId();
        }

        return false;
    }

    protected function isOwnerById(mixed $object, int|string $userId): bool
    {
        if (! method_exists($object, 'getOwnerId') && ! method_exists($object, 'getAuthorId')) {
            return false;
        }

        $ownerId = method_exists($object, 'getOwnerId') ? $object->getOwnerId() : $object->getAuthorId();

        return $ownerId === $userId;
    }
}
