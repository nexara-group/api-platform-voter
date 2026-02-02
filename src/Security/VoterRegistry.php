<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Security;

final class VoterRegistry
{
    /**
     * @var array<string, string> Mapping voter class => resource class
     */
    private array $voterToResourceMap = [];

    private bool $locked = false;

    public function register(string $voterClass, string $resourceClass): void
    {
        if ($this->locked) {
            throw new \LogicException(
                'Cannot register voters after registry has been locked. ' .
                'Voters should be registered during container compilation.'
            );
        }

        $this->voterToResourceMap[$voterClass] = $resourceClass;
    }

    public function lock(): void
    {
        $this->locked = true;
    }

    public function getResourceClass(string $voterClass): ?string
    {
        return $this->voterToResourceMap[$voterClass] ?? null;
    }

    public function getVoterClass(string $resourceClass): ?string
    {
        $key = array_search($resourceClass, $this->voterToResourceMap, true);

        return $key !== false ? $key : null;
    }

    /**
     * @return array<string, string>
     */
    public function getAllMappings(): array
    {
        return $this->voterToResourceMap;
    }

    public function hasVoter(string $voterClass): bool
    {
        return isset($this->voterToResourceMap[$voterClass]);
    }

    public function hasResource(string $resourceClass): bool
    {
        return in_array($resourceClass, $this->voterToResourceMap, true);
    }
}
