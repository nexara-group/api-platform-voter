<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Tests\Performance;

use Nexara\ApiPlatformVoter\Voter\CrudVoter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class VoterPerformanceTest extends TestCase
{
    public function testVoterPerformanceWithManyChecks(): void
    {
        $voter = new BenchmarkVoter();
        $token = $this->createMock(TokenInterface::class);
        $subject = new \stdClass();

        $iterations = 1000;
        $startTime = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            $voter->vote($token, $subject, ['benchmark:read']);
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;
        $averageTime = ($duration / $iterations) * 1000;

        $this->assertLessThan(1.0, $averageTime, 'Average voter decision time should be less than 1ms');

        echo sprintf(
            "\nVoter Performance: %d checks in %.4f seconds (%.4f ms average)\n",
            $iterations,
            $duration,
            $averageTime
        );
    }

    public function testVoterMemoryUsage(): void
    {
        $voter = new BenchmarkVoter();
        $token = $this->createMock(TokenInterface::class);

        $memoryBefore = memory_get_usage();

        for ($i = 0; $i < 1000; $i++) {
            $subject = new \stdClass();
            $voter->vote($token, $subject, ['benchmark:read']);
        }

        $memoryAfter = memory_get_usage();
        $memoryUsed = ($memoryAfter - $memoryBefore) / 1024;

        $this->assertLessThan(100, $memoryUsed, 'Memory usage should be less than 100KB for 1000 checks');

        echo sprintf("\nMemory Usage: %.2f KB\n", $memoryUsed);
    }
}

final class BenchmarkVoter extends CrudVoter
{
    public function __construct()
    {
        $this->setPrefix('benchmark');
        $this->setResourceClasses(\stdClass::class);
    }

    protected function canRead(mixed $object): bool
    {
        return true;
    }
}
