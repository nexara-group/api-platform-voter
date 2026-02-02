<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Cache;

final class VoterResultMemoizer
{
    private array $cache = [];

    private bool $enabled = true;

    public function enable(): void
    {
        $this->enabled = true;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function has(string $attribute, mixed $subject): bool
    {
        if (! $this->enabled) {
            return false;
        }

        $key = $this->buildKey($attribute, $subject);

        return isset($this->cache[$key]);
    }

    public function get(string $attribute, mixed $subject): ?bool
    {
        if (! $this->enabled) {
            return null;
        }

        $key = $this->buildKey($attribute, $subject);

        return $this->cache[$key] ?? null;
    }

    public function set(string $attribute, mixed $subject, bool $result): void
    {
        if (! $this->enabled) {
            return;
        }

        $key = $this->buildKey($attribute, $subject);
        $this->cache[$key] = $result;
    }

    public function clear(): void
    {
        $this->cache = [];
    }

    public function getStats(): array
    {
        return [
            'enabled' => $this->enabled,
            'cached_results' => count($this->cache),
            'memory_usage' => memory_get_usage(true),
        ];
    }

    private function buildKey(string $attribute, mixed $subject): string
    {
        $subjectKey = $this->serializeSubject($subject);

        return hash('xxh3', $attribute . '|' . $subjectKey);
    }

    private function serializeSubject(mixed $subject): string
    {
        if (is_object($subject)) {
            $id = spl_object_id($subject);

            if (method_exists($subject, 'getId')) {
                return $subject::class . '#' . $subject->getId() . '@' . $id;
            }

            return $subject::class . '@' . $id;
        }

        if (is_array($subject)) {
            $parts = [];
            foreach ($subject as $item) {
                $parts[] = $this->serializeSubject($item);
            }

            return 'array[' . implode(',', $parts) . ']';
        }

        if (is_scalar($subject)) {
            return (string) $subject;
        }

        return 'unknown';
    }
}
