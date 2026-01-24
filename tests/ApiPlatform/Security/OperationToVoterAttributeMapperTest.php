<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Tests\ApiPlatform\Security;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Nexara\ApiPlatformVoter\ApiPlatform\Security\OperationToVoterAttributeMapper;
use PHPUnit\Framework\TestCase;

final class OperationToVoterAttributeMapperTest extends TestCase
{
    public function testCrudMapping(): void
    {
        $mapper = new OperationToVoterAttributeMapper(true);

        self::assertSame('video:read', $mapper->map(new Get(), 'video'));
        self::assertSame('video:list', $mapper->map(new GetCollection(), 'video'));
        self::assertSame('video:create', $mapper->map(new Post(), 'video'));
        self::assertSame('video:update', $mapper->map(new Put(), 'video'));
        self::assertSame('video:delete', $mapper->map(new Delete(), 'video'));
    }
}
