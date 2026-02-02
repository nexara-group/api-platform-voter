# Upgrade Guide to 0.2.0

## Breaking Changes

Version 0.2.0 introduces breaking changes to improve the bundle's architecture and type safety.

### 1. Namespace Reorganization

**Voter Classes:**
```php
// Before (0.1.x)
use Nexara\ApiPlatformVoter\Security\Voter\CrudVoter;
use Nexara\ApiPlatformVoter\Security\Voter\AutoConfiguredCrudVoter;

// After (0.2.0+)
use Nexara\ApiPlatformVoter\Voter\CrudVoter;
use Nexara\ApiPlatformVoter\Voter\AutoConfiguredCrudVoter;
```

**Provider/Processor (if you extended them):**
```php
// Before (0.1.x)
use Nexara\ApiPlatformVoter\ApiPlatform\State\SecurityProvider;

// After (0.2.0+)
use Nexara\ApiPlatformVoter\Provider\SecurityProvider;
```

**Metadata (if you used them directly):**
```php
// Before (0.1.x)
use Nexara\ApiPlatformVoter\ApiPlatform\Security\ResourceAccessMetadata;

// After (0.2.0+)
use Nexara\ApiPlatformVoter\Metadata\ResourceAccessMetadata;
```

### 2. Attribute Renamed

The main attribute has been renamed for clarity:

```php
// Before (0.1.x)
use Nexara\ApiPlatformVoter\Attribute\ApiResourceVoter;

#[ApiResourceVoter(voter: ArticleVoter::class)]
class Article {}

// After (0.2.0+)
use Nexara\ApiPlatformVoter\Attribute\Secured;

#[Secured(voter: ArticleVoter::class)]
class Article {}
```

## Migration Steps

### Step 1: Update Composer

```bash
composer require nexara/api-platform-voter:^0.2
```

### Step 2: Update Voter Imports

Find and replace in your voter classes:

```bash
# Linux/Mac
find src -type f -name "*.php" -exec sed -i 's/Nexara\\ApiPlatformVoter\\Security\\Voter\\/Nexara\\ApiPlatformVoter\\Voter\\/g' {} +

# Or manually update:
# Nexara\ApiPlatformVoter\Security\Voter\ → Nexara\ApiPlatformVoter\Voter\
```

### Step 3: Update Resource Attributes

Find and replace in your entity classes:

```bash
# Find all uses
grep -r "ApiResourceVoter" src/Entity/

# Replace manually or with sed:
sed -i 's/ApiResourceVoter/Secured/g' src/Entity/*.php
```

### Step 4: Clear Cache

```bash
php bin/console cache:clear
php bin/console cache:warmup
```

### Step 5: Verify

Run your tests to ensure everything works:

```bash
php bin/phpunit
```

## New Features in 0.2.0

### Context-Aware Authorization
```php
use Nexara\ApiPlatformVoter\Security\Context\RequestContext;

protected function canRead(mixed $object): bool
{
    $context = $this->requestContextFactory->createFromRequest();
    
    // Check IP restrictions
    if (!$context->isIpInRange('192.168.1.0/24')) {
        return false;
    }
    
    // Check business hours
    if (!$context->isBusinessHours(9, 17)) {
        return false;
    }
    
    return true;
}
```

### Multi-Tenancy Support
```php
use Nexara\ApiPlatformVoter\Voter\Trait\TenantAwareVoterTrait;

final class ArticleVoter extends CrudVoter
{
    use TenantAwareVoterTrait;
    
    protected function canRead(mixed $object): bool
    {
        return $this->belongsToCurrentTenant($object);
    }
}
```

### Testing Utilities
```php
use Nexara\ApiPlatformVoter\Testing\VoterTestCase;

final class ArticleVoterTest extends VoterTestCase
{
    protected function createVoter(): VoterInterface
    {
        return new ArticleVoter($this->security);
    }
    
    public function testCanRead(): void
    {
        $article = new Article();
        $this->assertVoterGrants('article:read', $article);
    }
}
```

### Performance Improvements
- Metadata precompilation during cache warmup
- Result memoization within requests
- Reflection caching
- Optimized voter registry

### Enhanced Documentation
- 7 comprehensive guides in `docs/`
- Common authorization patterns cookbook
- Field-level authorization examples
- Multi-tenancy setup guide

## Troubleshooting

### Class Not Found Errors

If you see errors like:
```
Class "Nexara\ApiPlatformVoter\Security\Voter\CrudVoter" not found
```

Update the namespace in your voter class:
```php
use Nexara\ApiPlatformVoter\Voter\CrudVoter; // ✅ Correct
```

### Attribute Not Found

If you see:
```
Unknown attribute "ApiResourceVoter"
```

Update the attribute import and usage:
```php
use Nexara\ApiPlatformVoter\Attribute\Secured;

#[Secured(voter: ArticleVoter::class)]
```

### Cache Issues

Clear all caches:
```bash
rm -rf var/cache/*
php bin/console cache:clear --no-warmup
php bin/console cache:warmup
```

## Estimated Migration Time

- **Small projects** (< 10 voters): 15-30 minutes
- **Medium projects** (10-50 voters): 30-60 minutes
- **Large projects** (50+ voters): 1-2 hours

Most of the migration is find-and-replace operations.

## Support

If you encounter issues during migration:
- 📖 [Documentation](https://github.com/nexara-group/api-platform-voter/tree/main/docs)
- 🐛 [Issue Tracker](https://github.com/nexara-group/api-platform-voter/issues)
- 💬 [Discussions](https://github.com/nexara-group/api-platform-voter/discussions)
