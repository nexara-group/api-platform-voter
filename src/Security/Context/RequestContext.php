<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Security\Context;

use DateTimeInterface;

final class RequestContext
{
    public function __construct(
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
        public readonly ?DateTimeInterface $requestTime = null,
        public readonly array $headers = [],
        public readonly ?string $method = null,
        public readonly ?string $uri = null,
        public readonly array $custom = [],
    ) {
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    public function isIpInRange(string $cidr): bool
    {
        if ($this->ipAddress === null) {
            return false;
        }

        [$range, $netmask] = explode('/', $cidr, 2) + [null, 32];
        $rangeDecimal = ip2long($range);
        $ipDecimal = ip2long($this->ipAddress);
        $wildcardDecimal = 2 ** (32 - (int) $netmask) - 1;
        $netmaskDecimal = ~$wildcardDecimal;

        return ($ipDecimal & $netmaskDecimal) === ($rangeDecimal & $netmaskDecimal);
    }

    public function isBusinessHours(?string $timezone = null): bool
    {
        if ($this->requestTime === null) {
            return true;
        }

        $time = $this->requestTime;
        if ($timezone !== null) {
            $time = $time->setTimezone(new \DateTimeZone($timezone));
        }

        $hour = (int) $time->format('G');
        $dayOfWeek = (int) $time->format('N');

        return $dayOfWeek >= 1 && $dayOfWeek <= 5 && $hour >= 9 && $hour < 17;
    }

    public function isDayOfWeek(int ...$days): bool
    {
        if ($this->requestTime === null) {
            return false;
        }

        $currentDay = (int) $this->requestTime->format('N');

        return in_array($currentDay, $days, true);
    }

    public function isTimeInRange(string $startTime, string $endTime, ?string $timezone = null): bool
    {
        if ($this->requestTime === null) {
            return false;
        }

        $current = $this->requestTime;
        if ($timezone !== null) {
            $current = $current->setTimezone(new \DateTimeZone($timezone));
        }

        $start = \DateTimeImmutable::createFromFormat('H:i', $startTime, $current->getTimezone());
        $end = \DateTimeImmutable::createFromFormat('H:i', $endTime, $current->getTimezone());

        if ($start === false || $end === false) {
            return false;
        }

        return $current >= $start && $current <= $end;
    }

    public function getCustomValue(string $key): mixed
    {
        return $this->custom[$key] ?? null;
    }
}
