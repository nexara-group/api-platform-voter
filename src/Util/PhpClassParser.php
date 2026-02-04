<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Util;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

final class PhpClassParser
{
    private readonly \PhpParser\Parser $parser;

    private readonly NodeFinder $nodeFinder;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForNewestSupportedVersion();
        $this->nodeFinder = new NodeFinder();
    }

    public function extractClassInfo(string $filePath): ?array
    {
        if (! file_exists($filePath)) {
            return null;
        }

        $code = file_get_contents($filePath);
        if ($code === false) {
            return null;
        }

        try {
            $ast = $this->parser->parse($code);
            if ($ast === null) {
                return null;
            }

            $namespace = $this->findNamespace($ast);
            $className = $this->findClassName($ast);

            if ($namespace === null || $className === null) {
                return null;
            }

            return [
                'namespace' => $namespace,
                'class' => $className,
                'fqcn' => $namespace . '\\' . $className,
            ];
        } catch (\Exception) {
            return null;
        }
    }

    private function findNamespace(array $ast): ?string
    {
        $namespaceNode = $this->nodeFinder->findFirstInstanceOf($ast, Node\Stmt\Namespace_::class);

        if ($namespaceNode instanceof Node\Stmt\Namespace_ && $namespaceNode->name) {
            return $namespaceNode->name->toString();
        }

        return null;
    }

    private function findClassName(array $ast): ?string
    {
        $classNode = $this->nodeFinder->findFirstInstanceOf($ast, Node\Stmt\Class_::class);

        if ($classNode instanceof Node\Stmt\Class_ && $classNode->name) {
            return $classNode->name->toString();
        }

        return null;
    }
}
