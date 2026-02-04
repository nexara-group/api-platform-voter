<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Voter;

/**
 * Fluent builder for voter configuration (PHP 2025 best practices).
 *
 * Provides a modern, chainable API for configuring voters with zero boilerplate.
 *
 * @example
 * ```php
 * $this->configure()
 *     ->prefix('article')
 *     ->resource(Article::class)
 *     ->autoDiscoverOperations();
 * ```
 */
final class VoterConfigBuilder
{
    private ?string $prefix = null;

    /**
     * @var array<int, class-string>
     */
    private array $resourceClasses = [];

    /**
     * @var array<int, string>
     */
    private array $customOperations = [];

    private bool $autoDiscover = false;

    public function __construct(
        private readonly CrudVoter $voter,
    ) {
    }

    /**
     * Set the voter attribute prefix.
     *
     * @example prefix('article') -> 'article:read', 'article:update', etc.
     */
    public function prefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Add a single resource class.
     *
     * @param class-string $resourceClass
     */
    public function resource(string $resourceClass): self
    {
        $this->resourceClasses[] = $resourceClass;
        return $this;
    }

    /**
     * Add multiple resource classes.
     *
     * @param class-string ...$resourceClasses
     */
    public function resources(string ...$resourceClasses): self
    {
        $this->resourceClasses = [...$this->resourceClasses, ...$resourceClasses];
        return $this;
    }

    /**
     * Add a custom operation name.
     *
     * @example operation('publish') -> canPublish() method will be called
     */
    public function operation(string $operation): self
    {
        $this->customOperations[] = $operation;
        return $this;
    }

    /**
     * Add multiple custom operations.
     *
     * @param string ...$operations
     */
    public function operations(string ...$operations): self
    {
        $this->customOperations = [...$this->customOperations, ...$operations];
        return $this;
    }

    /**
     * Auto-discover custom operations from can* methods.
     *
     * Automatically finds all canSomething() methods and registers them as custom operations.
     */
    public function autoDiscoverOperations(): self
    {
        $this->autoDiscover = true;
        return $this;
    }

    /**
     * Apply configuration to the voter.
     *
     * Called automatically when builder goes out of scope.
     *
     * @internal
     */
    public function apply(): void
    {
        if ($this->prefix !== null) {
            $this->voter->setPrefix($this->prefix);
        }

        if ($this->resourceClasses !== []) {
            $this->voter->setResourceClasses($this->resourceClasses);
        }

        if ($this->customOperations !== []) {
            $this->voter->setCustomOperations($this->customOperations);
        }

        if ($this->autoDiscover) {
            $this->voter->autoDiscoverOperations();
        }
    }

    /**
     * Auto-apply configuration when builder is destroyed.
     */
    public function __destruct()
    {
        $this->apply();
    }
}
