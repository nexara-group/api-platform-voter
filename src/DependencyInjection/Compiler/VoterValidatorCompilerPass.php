<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\DependencyInjection\Compiler;

use Nexara\ApiPlatformVoter\Voter\CrudVoter;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class VoterValidatorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $voterServices = $container->findTaggedServiceIds('security.voter');

        foreach ($voterServices as $id => $tags) {
            $definition = $container->getDefinition($id);
            $class = $definition->getClass();

            if ($class === null || ! class_exists($class)) {
                continue;
            }

            if (! is_subclass_of($class, CrudVoter::class)) {
                continue;
            }

            $this->validateVoter($class);
        }
    }

    private function validateVoter(string $voterClass): void
    {
        $reflection = new ReflectionClass($voterClass);

        $customOperations = $this->extractCustomOperations($reflection);

        foreach ($customOperations as $operation) {
            $methodName = 'can' . $this->toCamelCase($operation);

            if (! $reflection->hasMethod($methodName)) {
                trigger_error(
                    sprintf(
                        'Voter "%s" declares custom operation "%s" but does not implement method "%s()". ' .
                        'Either implement the method or override canCustomOperation().',
                        $voterClass,
                        $operation,
                        $methodName
                    ),
                    E_USER_WARNING
                );
            } else {
                $this->validateMethodSignature($reflection, $methodName);
            }
        }
    }

    private function extractCustomOperations(ReflectionClass $reflection): array
    {
        $property = $reflection->hasProperty('customOperations') ? $reflection->getProperty('customOperations') : null;

        if ($property === null) {
            return [];
        }

        $property->setAccessible(true);
        $instance = $reflection->newInstanceWithoutConstructor();
        $operations = $property->getValue($instance);

        return is_array($operations) ? $operations : [];
    }

    private function validateMethodSignature(ReflectionClass $reflection, string $methodName): void
    {
        $method = $reflection->getMethod($methodName);

        if (! $method->isProtected() && ! $method->isPublic()) {
            trigger_error(
                sprintf(
                    'Method "%s::%s()" should be protected or public.',
                    $reflection->getName(),
                    $methodName
                ),
                E_USER_NOTICE
            );
        }

        $parameters = $method->getParameters();
        $expectedParams = ['object', 'previousObject'];

        if (count($parameters) !== count($expectedParams)) {
            trigger_error(
                sprintf(
                    'Method "%s::%s()" should have exactly 2 parameters: $object and $previousObject.',
                    $reflection->getName(),
                    $methodName
                ),
                E_USER_NOTICE
            );
        }
    }

    private function toCamelCase(string $str): string
    {
        $str = str_replace(['-', '_'], ' ', $str);
        $str = ucwords($str);

        return str_replace(' ', '', $str);
    }
}
