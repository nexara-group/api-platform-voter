<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Tests\ApiPlatform\Security;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\SubjectResolver;
use PHPUnit\Framework\TestCase;

final class SubjectResolverErrorTest extends TestCase
{
    private SubjectResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new SubjectResolver();
    }

    public function testResolveGetCollectionReturnsResourceClass(): void
    {
        $operation = new GetCollection(class: 'App\\Entity\\Article');
        $data = ['item1', 'item2'];
        $context = [
            'resource_class' => 'App\\Entity\\Article',
        ];

        $result = $this->resolver->resolve($operation, $data, $context);

        $this->assertSame('App\\Entity\\Article', $result);
    }

    public function testResolveGetReturnsData(): void
    {
        $operation = new Get();
        $data = new \stdClass();
        $context = [];

        $result = $this->resolver->resolve($operation, $data, $context);

        $this->assertSame($data, $result);
    }

    public function testResolvePostReturnsData(): void
    {
        $operation = new Post();
        $data = new \stdClass();
        $context = [];

        $result = $this->resolver->resolve($operation, $data, $context);

        $this->assertSame($data, $result);
    }

    public function testResolvePutReturnsArrayWithPreviousObject(): void
    {
        $operation = new Put();
        $data = new \stdClass();
        $previous = new \stdClass();
        $context = [
            'previous_object' => $previous,
        ];

        $result = $this->resolver->resolve($operation, $data, $context);

        $this->assertIsArray($result);
        $this->assertSame($data, $result[0]);
        $this->assertSame($previous, $result[1]);
    }

    public function testResolvePatchReturnsArrayWithPreviousData(): void
    {
        $operation = new Patch();
        $data = new \stdClass();
        $previous = new \stdClass();
        $context = [
            'previous_data' => $previous,
        ];

        $result = $this->resolver->resolve($operation, $data, $context);

        $this->assertIsArray($result);
        $this->assertSame($data, $result[0]);
        $this->assertSame($previous, $result[1]);
    }

    public function testResolveDeleteReturnsData(): void
    {
        $operation = new Delete();
        $data = new \stdClass();
        $context = [];

        $result = $this->resolver->resolve($operation, $data, $context);

        $this->assertSame($data, $result);
    }

    public function testResolveWithMissingPreviousObjectReturnsNull(): void
    {
        $operation = new Put();
        $data = new \stdClass();
        $context = [];

        $result = $this->resolver->resolve($operation, $data, $context);

        $this->assertIsArray($result);
        $this->assertSame($data, $result[0]);
        $this->assertNull($result[1]);
    }
}
