<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\ApiPlatform\Security;

use ApiPlatform\Metadata\Operation;

final class ChainSubjectResolver implements SubjectResolverInterface
{
    private array $strategies = [];

    /**
     * @param iterable<SubjectResolverStrategyInterface> $strategies
     */
    public function __construct(iterable $strategies)
    {
        $strategiesArray = $strategies instanceof \Traversable ? iterator_to_array($strategies) : $strategies;

        usort($strategiesArray, fn (SubjectResolverStrategyInterface $a, SubjectResolverStrategyInterface $b) => $b->getPriority() <=> $a->getPriority());

        $this->strategies = $strategiesArray;
    }

    public function resolve(Operation $operation, mixed $data, array $context): mixed
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($operation)) {
                return $strategy->resolve($operation, $data, $context);
            }
        }

        return $data;
    }
}
