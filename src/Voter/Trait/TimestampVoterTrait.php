<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Security\Voter\Trait;

use DateTimeInterface;

trait TimestampVoterTrait
{
    protected function isCreatedRecently(mixed $object, int $minutes = 60): bool
    {
        if (! method_exists($object, 'getCreatedAt')) {
            return false;
        }

        $createdAt = $object->getCreatedAt();

        if (! $createdAt instanceof DateTimeInterface) {
            return false;
        }

        $threshold = new \DateTimeImmutable("-{$minutes} minutes");

        return $createdAt > $threshold;
    }

    protected function isUpdatedRecently(mixed $object, int $minutes = 60): bool
    {
        if (! method_exists($object, 'getUpdatedAt')) {
            return false;
        }

        $updatedAt = $object->getUpdatedAt();

        if (! $updatedAt instanceof DateTimeInterface) {
            return false;
        }

        $threshold = new \DateTimeImmutable("-{$minutes} minutes");

        return $updatedAt > $threshold;
    }

    protected function isCreatedBefore(mixed $object, DateTimeInterface $date): bool
    {
        if (! method_exists($object, 'getCreatedAt')) {
            return false;
        }

        $createdAt = $object->getCreatedAt();

        if (! $createdAt instanceof DateTimeInterface) {
            return false;
        }

        return $createdAt < $date;
    }

    protected function isCreatedAfter(mixed $object, DateTimeInterface $date): bool
    {
        if (! method_exists($object, 'getCreatedAt')) {
            return false;
        }

        $createdAt = $object->getCreatedAt();

        if (! $createdAt instanceof DateTimeInterface) {
            return false;
        }

        return $createdAt > $date;
    }

    protected function wasModified(mixed $object): bool
    {
        if (! method_exists($object, 'getCreatedAt') || ! method_exists($object, 'getUpdatedAt')) {
            return false;
        }

        $createdAt = $object->getCreatedAt();
        $updatedAt = $object->getUpdatedAt();

        if (! $createdAt instanceof DateTimeInterface || ! $updatedAt instanceof DateTimeInterface) {
            return false;
        }

        return $updatedAt > $createdAt;
    }
}
