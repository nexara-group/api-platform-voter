<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Security\Delegation;

use Symfony\Component\Security\Core\User\UserInterface;

final class DelegatedAuthorizationChecker
{
    private array $delegations = [];

    public function addDelegation(
        UserInterface|string $delegator,
        UserInterface|string $delegatee,
        string $permission,
        ?string $resourceClass = null,
        ?int $resourceId = null,
        ?\DateTimeInterface $expiresAt = null
    ): void {
        $delegatorId = $this->extractUserId($delegator);
        $delegateeId = $this->extractUserId($delegatee);

        $key = $this->buildKey($delegatorId, $delegateeId, $permission, $resourceClass, $resourceId);

        $this->delegations[$key] = [
            'delegator_id' => $delegatorId,
            'delegatee_id' => $delegateeId,
            'permission' => $permission,
            'resource_class' => $resourceClass,
            'resource_id' => $resourceId,
            'expires_at' => $expiresAt,
            'created_at' => new \DateTimeImmutable(),
        ];
    }

    public function revokeDelegation(
        UserInterface|string $delegator,
        UserInterface|string $delegatee,
        string $permission,
        ?string $resourceClass = null,
        ?int $resourceId = null
    ): void {
        $delegatorId = $this->extractUserId($delegator);
        $delegateeId = $this->extractUserId($delegatee);

        $key = $this->buildKey($delegatorId, $delegateeId, $permission, $resourceClass, $resourceId);

        unset($this->delegations[$key]);
    }

    public function isDelegated(
        UserInterface|string $delegatee,
        string $permission,
        ?string $resourceClass = null,
        ?int $resourceId = null
    ): bool {
        $delegateeId = $this->extractUserId($delegatee);
        $now = new \DateTimeImmutable();

        foreach ($this->delegations as $delegation) {
            if ($delegation['delegatee_id'] !== $delegateeId) {
                continue;
            }

            if ($delegation['permission'] !== $permission) {
                continue;
            }

            if ($delegation['resource_class'] !== null && $delegation['resource_class'] !== $resourceClass) {
                continue;
            }

            if ($delegation['resource_id'] !== null && $delegation['resource_id'] !== $resourceId) {
                continue;
            }

            if ($delegation['expires_at'] !== null && $delegation['expires_at'] < $now) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function getDelegations(UserInterface|string $delegatee): array
    {
        $delegateeId = $this->extractUserId($delegatee);
        $now = new \DateTimeImmutable();

        return array_values(array_filter(
            $this->delegations,
            fn (array $delegation) => $delegation['delegatee_id'] === $delegateeId
                && ($delegation['expires_at'] === null || $delegation['expires_at'] >= $now)
        ));
    }

    public function clearExpiredDelegations(): int
    {
        $now = new \DateTimeImmutable();
        $count = 0;

        foreach ($this->delegations as $key => $delegation) {
            if ($delegation['expires_at'] !== null && $delegation['expires_at'] < $now) {
                unset($this->delegations[$key]);
                $count++;
            }
        }

        return $count;
    }

    private function extractUserId(UserInterface|string $user): string
    {
        if ($user instanceof UserInterface) {
            return $user->getUserIdentifier();
        }

        return $user;
    }

    private function buildKey(
        string $delegatorId,
        string $delegateeId,
        string $permission,
        ?string $resourceClass,
        ?int $resourceId
    ): string {
        return hash('xxh3', implode('|', [
            $delegatorId,
            $delegateeId,
            $permission,
            $resourceClass ?? 'null',
            $resourceId ?? 'null',
        ]));
    }
}
