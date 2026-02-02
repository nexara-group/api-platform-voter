<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Security\VoterGroup;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class VoterGroupRegistry
{
    private array $groups = [];

    public function addGroup(string $groupName): void
    {
        if (! isset($this->groups[$groupName])) {
            $this->groups[$groupName] = [];
        }
    }

    public function addVoterToGroup(string $groupName, VoterInterface|string $voter): void
    {
        if (! isset($this->groups[$groupName])) {
            $this->groups[$groupName] = [];
        }

        $voterId = $voter instanceof VoterInterface ? $voter::class : $voter;

        if (! in_array($voterId, $this->groups[$groupName], true)) {
            $this->groups[$groupName][] = $voterId;
        }
    }

    public function removeVoterFromGroup(string $groupName, VoterInterface|string $voter): void
    {
        if (! isset($this->groups[$groupName])) {
            return;
        }

        $voterId = $voter instanceof VoterInterface ? $voter::class : $voter;

        $this->groups[$groupName] = array_values(array_filter(
            $this->groups[$groupName],
            fn ($id) => $id !== $voterId
        ));
    }

    public function getVotersByGroup(string $groupName): array
    {
        return $this->groups[$groupName] ?? [];
    }

    public function getAllGroups(): array
    {
        return array_keys($this->groups);
    }

    public function hasGroup(string $groupName): bool
    {
        return isset($this->groups[$groupName]);
    }

    public function getGroupsForVoter(VoterInterface|string $voter): array
    {
        $voterId = $voter instanceof VoterInterface ? $voter::class : $voter;
        $groups = [];

        foreach ($this->groups as $groupName => $voters) {
            if (in_array($voterId, $voters, true)) {
                $groups[] = $groupName;
            }
        }

        return $groups;
    }

    public function clear(): void
    {
        $this->groups = [];
    }
}
