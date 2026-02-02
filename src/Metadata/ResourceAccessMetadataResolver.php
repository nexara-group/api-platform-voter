<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Metadata;

use Nexara\ApiPlatformVoter\Attribute\Secured;
use Psr\Cache\CacheItemPoolInterface;
use ReflectionClass;

/**
 * Resolves resource access metadata from ApiResourceVoter attributes.
 *
 * @internal
 */
final class ResourceAccessMetadataResolver implements ResourceAccessMetadataResolverInterface
{
    public function __construct(
        private readonly ?CacheItemPoolInterface $cachePool = null,
    ) {
    }

    public function resolve(string $resourceClass): ResourceAccessMetadata
    {
        $cacheKey = 'nexara_apivoter.' . md5($resourceClass);

        if ($this->cachePool) {
            $item = $this->cachePool->getItem($cacheKey);
            if ($item->isHit()) {
                $value = $item->get();
                if (is_array($value)
                    && array_key_exists('protected', $value)
                    && array_key_exists('prefix', $value)
                    && array_key_exists('voter', $value)
                ) {
                    return new ResourceAccessMetadata((bool) $value['protected'], $value['prefix'], $value['voter']);
                }
            }
        }

        if (! class_exists($resourceClass)) {
            return new ResourceAccessMetadata(false, null, null);
        }

        $ref = new ReflectionClass($resourceClass);
        $attrs = $ref->getAttributes(Secured::class);

        if ($attrs === []) {
            $metadata = new ResourceAccessMetadata(false, null, null);
        } else {
            /** @var Secured $cfg */
            $cfg = $attrs[0]->newInstance();

            $prefix = $cfg->prefix;
            if (! is_string($prefix) || $prefix === '') {
                $prefix = strtolower($ref->getShortName());
            }

            $metadata = new ResourceAccessMetadata(true, $prefix, $cfg->voter);
        }

        if ($this->cachePool) {
            $item = $this->cachePool->getItem($cacheKey);
            $item->set([
                'protected' => $metadata->protected,
                'prefix' => $metadata->prefix,
                'voter' => $metadata->voter,
            ]);
            $this->cachePool->save($item);
        }

        return $metadata;
    }
}
