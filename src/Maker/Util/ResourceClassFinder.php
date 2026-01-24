<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Maker\Util;

use ApiPlatform\Metadata\ApiResource;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

final class ResourceClassFinder
{
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return list<class-string>
     */
    public function findApiResources(): array
    {
        $srcDir = rtrim($this->projectDir, '/') . '/src';
        if (! is_dir($srcDir)) {
            return [];
        }

        $parser = (new ParserFactory())->createForNewestSupportedVersion();

        $resources = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($srcDir, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file instanceof \SplFileInfo) {
                continue;
            }

            if ($file->getExtension() !== 'php') {
                continue;
            }

            $code = @file_get_contents($file->getPathname());
            if (! is_string($code) || $code === '') {
                continue;
            }

            try {
                $ast = $parser->parse($code);
            } catch (\Throwable) {
                continue;
            }

            if (! is_array($ast)) {
                continue;
            }

            $collector = new class() extends NodeVisitorAbstract {
                /**
                 * @var list<class-string>
                 */
                public array $classes = [];

                /**
                 * @var array<string, string>
                 */
                private array $uses = [];

                private ?string $namespace = null;

                public function enterNode(Node $node): ?int
                {
                    if ($node instanceof Node\Stmt\Namespace_) {
                        $this->namespace = $node->name?->toString();
                        $this->uses = [];
                    }

                    if ($node instanceof Node\Stmt\Use_) {
                        foreach ($node->uses as $use) {
                            $alias = $use->alias?->toString() ?? $use->name->getLast();
                            $this->uses[$alias] = $use->name->toString();
                        }
                    }

                    if ($node instanceof Node\Stmt\Class_ && $node->name) {
                        if (! $this->hasApiResourceAttribute($node->attrGroups)) {
                            return null;
                        }

                        $className = $node->name->toString();
                        $fqcn = $this->namespace ? $this->namespace . '\\' . $className : $className;

                        if (class_exists($fqcn)) {
                            $this->classes[] = $fqcn;
                        }
                    }

                    return null;
                }

                /**
                 * @param Node\AttributeGroup[] $attrGroups
                 */
                private function hasApiResourceAttribute(array $attrGroups): bool
                {
                    foreach ($attrGroups as $group) {
                        foreach ($group->attrs as $attr) {
                            $name = $attr->name->toString();
                            $resolved = $this->resolveName($name);

                            if ($resolved === ApiResource::class) {
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
            };

            $traverser = new NodeTraverser();
            $traverser->addVisitor($collector);
            $traverser->traverse($ast);

            foreach ($collector->classes as $fqcn) {
                $resources[$fqcn] = true;
            }
        }

        $list = array_keys($resources);
        sort($list);

        return $list;
    }
}
