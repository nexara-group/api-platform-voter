<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Maker\Util;

use Nexara\ApiPlatformVoter\Attribute\ApiResourceVoter;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use ReflectionClass;

final class PhpResourceVoterAttributeAdder
{
    public function addToResourceClass(string $resourceClass, string $voterFqcn, ?string $prefix): void
    {
        if (! class_exists($resourceClass)) {
            throw new \RuntimeException("Resource class '{$resourceClass}' was not found.");
        }

        $ref = new ReflectionClass($resourceClass);
        $file = $ref->getFileName();
        if (! is_string($file) || $file === '' || ! is_file($file)) {
            throw new \RuntimeException("Cannot locate file for resource class '{$resourceClass}'.");
        }

        $code = file_get_contents($file);
        if (! is_string($code) || $code === '') {
            throw new \RuntimeException("Cannot read file '{$file}'.");
        }

        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        try {
            $ast = $parser->parse($code);
        } catch (\Throwable $e) {
            throw new \RuntimeException("Cannot parse '{$file}': {$e->getMessage()}");
        }

        if (! is_array($ast)) {
            throw new \RuntimeException("Cannot parse '{$file}'.");
        }

        $visitor = new class($resourceClass, $voterFqcn, $prefix) extends NodeVisitorAbstract {
            public function __construct(
                private readonly string $resourceClass,
                private readonly string $voterFqcn,
                private readonly ?string $prefix,
            ) {
            }

            private ?string $namespace = null;

            /**
             * @var array<string, string>
             */
            private array $uses = [];

            private bool $changed = false;

            public function enterNode(Node $node): ?int
            {
                if ($node instanceof Node\Stmt\Namespace_) {
                    $this->namespace = $node->name?->toString();
                    $this->uses = [];

                    foreach ($node->stmts as $stmt) {
                        if ($stmt instanceof Node\Stmt\Use_) {
                            foreach ($stmt->uses as $use) {
                                $alias = $use->alias?->toString() ?? $use->name->getLast();
                                $this->uses[$alias] = $use->name->toString();
                            }
                        }
                    }
                }

                if ($node instanceof Node\Stmt\Class_ && $node->name) {
                    $className = $node->name->toString();
                    $fqcn = $this->namespace ? $this->namespace . '\\' . $className : $className;

                    if ($fqcn !== $this->resourceClass) {
                        return null;
                    }

                    if ($this->hasApiResourceVoterAttribute($node->attrGroups)) {
                        return null;
                    }

                    $args = [
                        new Node\Arg(
                            new Node\Expr\ClassConstFetch(
                                new Node\Name($this->shortClassName($this->voterFqcn)),
                                'class',
                            ),
                            false,
                            false,
                            [],
                            new Node\Identifier('voter'),
                        ),
                    ];

                    if (is_string($this->prefix) && $this->prefix !== '') {
                        $args[] = new Node\Arg(
                            new Node\Scalar\String_($this->prefix),
                            false,
                            false,
                            [],
                            new Node\Identifier('prefix'),
                        );
                    }

                    $node->attrGroups[] = new Node\AttributeGroup([
                        new Node\Attribute(
                            new Node\Name('ApiResourceVoter'),
                            $args,
                        ),
                    ]);

                    $this->changed = true;

                    return null;
                }

                return null;
            }

            public function afterTraverse(array $nodes): ?array
            {
                if (! $this->changed) {
                    return null;
                }

                $apiResourceVoterImport = ApiResourceVoter::class;
                $voterImport = $this->voterFqcn;

                return $this->ensureUses($nodes, [
                    $apiResourceVoterImport,
                    $voterImport,
                ]);
            }

            /**
             * @param Node\AttributeGroup[] $attrGroups
             */
            private function hasApiResourceVoterAttribute(array $attrGroups): bool
            {
                foreach ($attrGroups as $group) {
                    foreach ($group->attrs as $attr) {
                        $name = $attr->name->toString();
                        $resolved = $this->resolveName($name);

                        if ($resolved === ApiResourceVoter::class) {
                            return true;
                        }
                    }
                }

                return false;
            }

            private function resolveName(string $name): string
            {
                $name = ltrim($name, '\\');

                if (str_contains($name, '\\')) {
                    return $name;
                }

                return $this->uses[$name] ?? ($this->namespace ? $this->namespace . '\\' . $name : $name);
            }

            /**
             * @param Node[] $nodes
             * @param list<string> $imports
             * @return Node[]|null
             */
            private function ensureUses(array $nodes, array $imports): ?array
            {
                foreach ($nodes as $node) {
                    if (! $node instanceof Node\Stmt\Namespace_) {
                        continue;
                    }

                    $existing = [];
                    $firstUseIndex = null;

                    foreach ($node->stmts as $i => $stmt) {
                        if ($stmt instanceof Node\Stmt\Use_) {
                            $firstUseIndex ??= $i;
                            foreach ($stmt->uses as $use) {
                                $existing[$use->name->toString()] = true;
                            }
                        }
                    }

                    $newUses = [];
                    foreach ($imports as $fqcn) {
                        if (isset($existing[$fqcn])) {
                            continue;
                        }

                        $newUses[] = new Node\Stmt\Use_([
                            new Node\Stmt\UseUse(new Node\Name($fqcn)),
                        ]);
                    }

                    if ($newUses === []) {
                        return null;
                    }

                    if ($firstUseIndex === null) {
                        array_splice($node->stmts, 0, 0, $newUses);
                    } else {
                        array_splice($node->stmts, $firstUseIndex, 0, $newUses);
                    }

                    return $nodes;
                }

                return null;
            }

            private function shortClassName(string $fqcn): string
            {
                $fqcn = ltrim($fqcn, '\\');
                $parts = explode('\\', $fqcn);

                return end($parts) ?: $fqcn;
            }
        };

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $newAst = $traverser->traverse($ast);

        $printer = new Standard();
        $newCode = $printer->prettyPrintFile($newAst);

        file_put_contents($file, $newCode);
    }
}
