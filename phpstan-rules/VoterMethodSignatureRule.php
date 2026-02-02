<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\PHPStan\Rules;

use Nexara\ApiPlatformVoter\Security\Voter\CrudVoter;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassMethodNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * @implements Rule<InClassMethodNode>
 */
final class VoterMethodSignatureRule implements Rule
{
    private const CRUD_METHODS = [
        'canList' => 0,
        'canCreate' => 1,
        'canRead' => 1,
        'canUpdate' => 2,
        'canDelete' => 1,
    ];

    public function getNodeType(): string
    {
        return InClassMethodNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $scope->getClassReflection();
        
        if ($classReflection === null) {
            return [];
        }

        if (!$classReflection->isSubclassOf(CrudVoter::class)) {
            return [];
        }

        $method = $node->getOriginalNode();
        $methodName = $method->name->toString();

        if (!str_starts_with($methodName, 'can')) {
            return [];
        }

        $expectedParams = $this->getExpectedParameterCount($methodName);
        
        if ($expectedParams === null) {
            return [];
        }

        $actualParams = count($method->getParams());

        if ($actualParams !== $expectedParams) {
            return [
                RuleErrorBuilder::message(sprintf(
                    'Voter method %s::%s() should have exactly %d parameter(s), %d given.',
                    $classReflection->getName(),
                    $methodName,
                    $expectedParams,
                    $actualParams
                ))
                ->identifier('nexara.voterMethodSignature')
                ->build(),
            ];
        }

        return [];
    }

    private function getExpectedParameterCount(string $methodName): ?int
    {
        if (isset(self::CRUD_METHODS[$methodName])) {
            return self::CRUD_METHODS[$methodName];
        }

        if (str_starts_with($methodName, 'can') && $methodName !== 'canCustomOperation') {
            return 2;
        }

        return null;
    }
}
