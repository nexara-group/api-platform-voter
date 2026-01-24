<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Security\Voter;

use LogicException;
use Nexara\ApiPlatformVoter\Security\VoterRegistry;
use ReflectionClass;
use ReflectionMethod;

abstract class AutoConfiguredCrudVoter extends CrudVoter
{
    private bool $autoConfigured = false;

    private ?VoterRegistry $voterRegistry = null;

    public function setVoterRegistry(VoterRegistry $voterRegistry): void
    {
        $this->voterRegistry = $voterRegistry;
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (! $this->autoConfigured) {
            $this->autoConfigureFromMetadata();
        }

        return parent::supports($attribute, $subject);
    }

    protected function canCustomOperation(string $operation, mixed $object, mixed $previousObject): bool
    {
        $methodName = 'can' . $this->toCamelCase($operation);

        if (method_exists($this, $methodName)) {
            return $this->{$methodName}($object, $previousObject);
        }

        return false;
    }

    private function autoConfigureFromMetadata(): void
    {
        if ($this->autoConfigured) {
            return;
        }

        $resourceClass = $this->getResourceClassFromRegistry();

        if ($resourceClass) {
            $this->resourceClasses = [$resourceClass];

            $this->initializePrefixFromResource($resourceClass);

            $this->discoverCustomOperations();
        }

        $this->autoConfigured = true;
    }

    private function getResourceClassFromRegistry(): ?string
    {
        if (! $this->voterRegistry) {
            throw new LogicException(
                'VoterRegistry not injected. Make sure AutoConfiguredCrudVoter voters are autowired.'
            );
        }

        return $this->voterRegistry->getResourceClass(static::class);
    }

    private function initializePrefixFromResource(string $resourceClass): void
    {
        if (isset($this->prefix)) {
            return;
        }

        if (! class_exists($resourceClass)) {
            return;
        }

        $reflection = new ReflectionClass($resourceClass);
        $attributes = $reflection->getAttributes(\Nexara\ApiPlatformVoter\Attribute\ApiResourceVoter::class);

        if ($attributes !== []) {
            $attribute = $attributes[0]->newInstance();
            if ($attribute->prefix) {
                $this->prefix = $attribute->prefix;

                return;
            }
        }

        if (class_exists($resourceClass)) {
            $ref = new ReflectionClass($resourceClass);
            $this->prefix = strtolower($ref->getShortName());
        }
    }

    private function discoverCustomOperations(): void
    {
        $reflection = new ReflectionClass($this);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PUBLIC);

        $customOps = [];

        foreach ($methods as $method) {
            $name = $method->getName();

            if (in_array($name, ['canList', 'canCreate', 'canRead', 'canUpdate', 'canDelete', 'canCustomOperation'], true)) {
                continue;
            }

            if (str_starts_with($name, 'can') && strlen($name) > 3) {
                $operation = lcfirst(substr($name, 3));
                $customOps[] = $operation;
            }
        }

        $this->customOperations = $customOps;
    }

    private function toCamelCase(string $str): string
    {
        // Replace hyphens and underscores with spaces, then capitalize each word
        $str = str_replace(['-', '_'], ' ', $str);
        $str = ucwords($str);
        // Remove spaces
        return str_replace(' ', '', $str);
    }
}
