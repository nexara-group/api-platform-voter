# Common Authorization Patterns

This cookbook provides practical examples of common authorization patterns using the API Platform Voter bundle.

## Table of Contents

- [Owner-Based Authorization](#owner-based-authorization)
- [Role-Based Access Control](#role-based-access-control)
- [Time-Based Restrictions](#time-based-restrictions)
- [IP-Based Access Control](#ip-based-access-control)
- [Multi-Tenancy](#multi-tenancy)
- [Delegated Authorization](#delegated-authorization)
- [Conditional Access](#conditional-access)

## Owner-Based Authorization

Allow only the resource owner to update or delete their resources:

```php
use Nexara\ApiPlatformVoter\Security\Voter\CrudVoter;
use Nexara\ApiPlatformVoter\Security\Voter\Trait\OwnershipVoterTrait;
use Symfony\Bundle\SecurityBundle\Security;

final class ArticleVoter extends CrudVoter
{
    use OwnershipVoterTrait;

    public function __construct(
        private readonly Security $security,
    ) {
        $this->setPrefix('article');
        $this->setResourceClasses(Article::class);
    }

    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        $user = $this->security->getUser();
        
        // Only owner can update
        return $this->isOwner($object, $user);
    }

    protected function canDelete(mixed $object): bool
    {
        $user = $this->security->getUser();
        
        // Owner or admin can delete
        return $this->isOwner($object, $user) 
            || $this->security->isGranted('ROLE_ADMIN');
    }
}
```

## Role-Based Access Control

Implement role-based permissions with hierarchies:

```php
use Nexara\ApiPlatformVoter\Security\Voter\CrudVoter;
use Nexara\ApiPlatformVoter\Security\Voter\Trait\RoleCheckTrait;

final class ArticleVoter extends CrudVoter
{
    use RoleCheckTrait;

    protected function getSecurity(): Security
    {
        return $this->security;
    }

    protected function canCreate(mixed $object): bool
    {
        // Any authenticated user can create
        return $this->isAuthenticated();
    }

    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        // Author or editor can update
        return $this->hasAnyRole('ROLE_AUTHOR', 'ROLE_EDITOR');
    }

    protected function canDelete(mixed $object): bool
    {
        // Only admin can delete
        return $this->isAdmin();
    }
}
```

## Time-Based Restrictions

Restrict access based on time conditions:

```php
use Nexara\ApiPlatformVoter\Security\Voter\CrudVoter;
use Nexara\ApiPlatformVoter\Security\Voter\Trait\TimestampVoterTrait;
use Nexara\ApiPlatformVoter\Security\Context\RequestContext;

final class ArticleVoter extends CrudVoter
{
    use TimestampVoterTrait;

    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        $user = $this->security->getUser();
        
        // Owner can edit within 30 minutes of creation
        if ($this->isOwner($object, $user)) {
            return $this->isCreatedRecently($object, minutes: 30);
        }
        
        // Admin can always edit
        return $this->security->isGranted('ROLE_ADMIN');
    }
}
```

## IP-Based Access Control

Restrict access based on client IP address:

```php
use Nexara\ApiPlatformVoter\Security\Voter\CrudVoter;
use Nexara\ApiPlatformVoter\Security\Context\RequestContextFactory;

final class AdminResourceVoter extends CrudVoter
{
    public function __construct(
        private readonly Security $security,
        private readonly RequestContextFactory $contextFactory,
    ) {
        $this->setPrefix('admin_resource');
        $this->setResourceClasses(AdminResource::class);
    }

    protected function canRead(mixed $object): bool
    {
        $context = $this->contextFactory->create();
        
        // Only allow from internal network
        if ($context && !$context->isIpInRange('10.0.0.0/8')) {
            return false;
        }
        
        return $this->security->isGranted('ROLE_ADMIN');
    }
}
```

## Multi-Tenancy

Implement tenant-isolated authorization:

```php
use Nexara\ApiPlatformVoter\Security\Voter\CrudVoter;
use Nexara\ApiPlatformVoter\Security\Voter\Trait\TenantAwareVoterTrait;
use Nexara\ApiPlatformVoter\MultiTenancy\TenantContextInterface;

final class ArticleVoter extends CrudVoter
{
    use TenantAwareVoterTrait;

    public function __construct(
        private readonly Security $security,
        TenantContextInterface $tenantContext,
    ) {
        $this->setTenantContext($tenantContext);
        $this->setPrefix('article');
        $this->setResourceClasses(Article::class);
    }

    protected function canRead(mixed $object): bool
    {
        // Must belong to current tenant
        if (!$this->belongsToCurrentTenant($object)) {
            return false;
        }
        
        return true;
    }

    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        // Must belong to current tenant
        if (!$this->belongsToCurrentTenant($object)) {
            return false;
        }
        
        // And user must be the owner
        return $this->isOwner($object, $this->security->getUser());
    }
}
```

## Delegated Authorization

Allow users to delegate permissions:

```php
use Nexara\ApiPlatformVoter\Security\Voter\CrudVoter;
use Nexara\ApiPlatformVoter\Security\Delegation\DelegatedAuthorizationChecker;

final class ArticleVoter extends CrudVoter
{
    public function __construct(
        private readonly Security $security,
        private readonly DelegatedAuthorizationChecker $delegationChecker,
    ) {
        $this->setPrefix('article');
        $this->setResourceClasses(Article::class);
    }

    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return false;
        }
        
        // Owner can always update
        if ($this->isOwner($object, $user)) {
            return true;
        }
        
        // Check if permission was delegated to this user
        return $this->delegationChecker->isDelegated(
            $user,
            'article:update',
            Article::class,
            $object->getId()
        );
    }
}
```

## Conditional Access

Combine multiple conditions:

```php
final class ArticleVoter extends CrudVoter
{
    protected function canPublish(mixed $object, mixed $previousObject): bool
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return false;
        }
        
        // Must be in draft status
        if ($object->getStatus() !== 'draft') {
            return false;
        }
        
        // Owner can publish their own articles
        if ($this->isOwner($object, $user)) {
            return true;
        }
        
        // Editor can publish if during business hours
        if ($this->security->isGranted('ROLE_EDITOR')) {
            $context = $this->contextFactory->create();
            return $context && $context->isBusinessHours();
        }
        
        // Admin can always publish
        return $this->security->isGranted('ROLE_ADMIN');
    }
}
```

## Best Practices

1. **Use Traits**: Leverage provided traits for common patterns
2. **Fail Closed**: Default to denying access when in doubt
3. **Clear Logic**: Keep authorization logic simple and readable
4. **Test Thoroughly**: Write tests for all authorization scenarios
5. **Document Decisions**: Comment complex authorization rules
6. **Avoid Side Effects**: Keep voter methods pure (no state changes)
7. **Performance**: Cache expensive checks when possible
