# security vs securityPostDenormalize

Understanding the difference between these two API Platform security options and how they work with the Voter bundle.

## Overview

API Platform provides two security options:
- `security`: Checked **before** the resource is loaded and deserialized
- `securityPostDenormalize`: Checked **after** the resource is deserialized

The Voter bundle integrates at the **Provider** and **Processor** level, effectively replacing both.

## API Platform Native Security

### security

```php
#[ApiResource(
    operations: [
        new Put(
            security: "is_granted('ROLE_USER') and object.getAuthor() == user"
        )
    ]
)]
class Article { }
```

**When it runs:**
- After the resource is **loaded** (Provider)
- Before deserialization

**Problem:**
- Expression language is limited
- Hard to test
- Cannot compare with previous state

### securityPostDenormalize

```php
#[ApiResource(
    operations: [
        new Put(
            securityPostDenormalize: "is_granted('ROLE_USER') and object.getAuthor() == user"
        )
    ]
)]
class Article { }
```

**When it runs:**
- After the resource is **loaded** and **deserialized**
- Can access changed data

**Problem:**
- Still uses expression language
- Runs after deserialization (data already changed)
- Cannot easily rollback

## Voter Bundle Approach

The Voter bundle provides a better alternative:

```php
#[ApiResource]
#[ApiResourceVoter(voter: ArticleVoter::class)]
class Article { }
```

```php
final class ArticleVoter extends CrudVoter
{
    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        // $object = new state (after deserialization)
        // $previousObject = original state (before deserialization)
        
        $user = $this->security->getUser();
        
        if (!$user) {
            return false;
        }

        // Check ownership
        if ($object->getAuthor() !== $user) {
            return false;
        }

        // Prevent changing author
        if ($previousObject && $object->getAuthor() !== $previousObject->getAuthor()) {
            return $this->security->isGranted('ROLE_ADMIN');
        }

        return true;
    }
}
```

## Comparison

| Feature | security | securityPostDenormalize | Voter Bundle |
|---------|----------|------------------------|--------------|
| Type safety | ❌ | ❌ | ✅ |
| IDE support | ❌ | ❌ | ✅ |
| Testability | ⚠️ Limited | ⚠️ Limited | ✅ Excellent |
| Access to previous state | ❌ | ❌ | ✅ |
| Complex logic | ⚠️ Hard | ⚠️ Hard | ✅ Easy |
| Reusability | ❌ | ❌ | ✅ |
| Custom operations | ⚠️ Manual | ⚠️ Manual | ✅ Automatic |

## When Execution Happens

### Native API Platform

```
Request → Provider (load) → security check → Deserializer → securityPostDenormalize check → Processor
```

### With Voter Bundle

```
Request → SecurityProvider (voter check) → Decorated Provider (load) → Deserializer → SecurityProcessor (voter check) → Decorated Processor
```

## Migration Examples

### Example 1: Simple Role Check

**Before (native):**
```php
#[Put(security: "is_granted('ROLE_ADMIN')")]
```

**After (voter):**
```php
protected function canUpdate(mixed $object, mixed $previousObject): bool
{
    return $this->security->isGranted('ROLE_ADMIN');
}
```

### Example 2: Ownership Check

**Before (native):**
```php
#[Put(security: "is_granted('ROLE_USER') and object.getOwner() == user")]
```

**After (voter):**
```php
use Nexara\ApiPlatformVoter\Security\Voter\Trait\OwnershipVoterTrait;

final class ArticleVoter extends CrudVoter
{
    use OwnershipVoterTrait;

    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        $user = $this->security->getUser();
        return $this->isOwner($object, $user);
    }
}
```

### Example 3: Prevent Field Changes

**Before (native) - Not easily possible:**
```php
// Can't easily prevent specific field changes
```

**After (voter):**
```php
protected function canUpdate(mixed $object, mixed $previousObject): bool
{
    $user = $this->security->getUser();

    // Owner can update
    if (!$this->isOwner($object, $user)) {
        return false;
    }

    // Prevent changing author (even for owner)
    if ($previousObject && $object->getAuthor() !== $previousObject->getAuthor()) {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    // Prevent changing status to published
    if ($previousObject 
        && $previousObject->getStatus() !== 'published' 
        && $object->getStatus() === 'published'
    ) {
        return $this->security->isGranted('ROLE_MODERATOR');
    }

    return true;
}
```

## Using Both Together

You can use voter bundle alongside native security if needed:

```php
#[ApiResource(
    operations: [
        new Put(
            // Native security as first line of defense
            security: "is_granted('ROLE_USER')",
            // Voter handles complex logic
        )
    ]
)]
#[ApiResourceVoter(voter: ArticleVoter::class)]
class Article { }
```

However, this is usually not necessary as voters provide all needed functionality.

## Best Practices

1. **Prefer Voters**: Use the Voter bundle for all authorization logic
2. **Remove native security**: Clean up old `security` and `securityPostDenormalize` expressions
3. **Use previous state**: Leverage `$previousObject` to validate state transitions
4. **Type safety**: Enjoy IDE autocomplete and type checking
5. **Test easily**: Write proper unit tests for voter methods
6. **Centralize logic**: Keep all authorization logic in voters

## Common Pitfalls

### Pitfall 1: Using Both Without Understanding Order

```php
// ❌ Bad - Both check the same thing
#[Put(security: "is_granted('ROLE_USER') and object.getOwner() == user")]
#[ApiResourceVoter(voter: ArticleVoter::class)]

// ✅ Good - Only use voter
#[Put()]
#[ApiResourceVoter(voter: ArticleVoter::class)]
```

### Pitfall 2: Not Checking Previous State

```php
// ❌ Bad - Doesn't prevent changing critical fields
protected function canUpdate(mixed $object, mixed $previousObject): bool
{
    return $this->isOwner($object, $this->security->getUser());
}

// ✅ Good - Validates state transitions
protected function canUpdate(mixed $object, mixed $previousObject): bool
{
    if (!$this->isOwner($object, $this->security->getUser())) {
        return false;
    }

    // Prevent changing owner
    if ($previousObject && $object->getOwner() !== $previousObject->getOwner()) {
        return false;
    }

    return true;
}
```

### Pitfall 3: Complex Expression Language

```php
// ❌ Bad - Unreadable and hard to maintain
#[Put(securityPostDenormalize: "is_granted('ROLE_USER') and (object.getStatus() == 'draft' or (object.getStatus() == 'published' and is_granted('ROLE_MODERATOR')))")]

// ✅ Good - Clear and maintainable
protected function canUpdate(mixed $object, mixed $previousObject): bool
{
    $user = $this->security->getUser();

    if (!$user || !$this->isOwner($object, $user)) {
        return false;
    }

    // Draft articles can be edited by owner
    if ($object->getStatus() === 'draft') {
        return true;
    }

    // Published articles require moderator role
    if ($object->getStatus() === 'published') {
        return $this->security->isGranted('ROLE_MODERATOR');
    }

    return false;
}
```

## Summary

The Voter bundle provides a superior alternative to API Platform's native `security` and `securityPostDenormalize`:

- **Type-safe**: No expression language
- **Testable**: Standard PHP unit tests
- **Powerful**: Access to both current and previous state
- **Maintainable**: Clear, readable code
- **Reusable**: Share logic via traits

Migrate from native security expressions to voters for better code quality and maintainability.
