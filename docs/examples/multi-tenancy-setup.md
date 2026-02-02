# Multi-Tenancy Setup Guide

This guide demonstrates how to implement multi-tenancy with the API Platform Voter bundle.

## 1. Implement Tenant Interfaces

```php
// src/Entity/Tenant.php
use Nexara\ApiPlatformVoter\MultiTenancy\TenantInterface;

class Tenant implements TenantInterface
{
    private int $id;
    private string $identifier;
    private string $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
```

## 2. Make Entities Tenant-Aware

```php
// src/Entity/Article.php
use Nexara\ApiPlatformVoter\MultiTenancy\TenantAwareInterface;
use Nexara\ApiPlatformVoter\MultiTenancy\TenantInterface;

class Article implements TenantAwareInterface
{
    private int $id;
    private string $title;
    private ?Tenant $tenant = null;

    public function getTenant(): ?TenantInterface
    {
        return $this->tenant;
    }

    public function setTenant(?TenantInterface $tenant): void
    {
        $this->tenant = $tenant;
    }
}
```

## 3. Create Tenant Context

```php
// src/Security/TenantContext.php
use Nexara\ApiPlatformVoter\MultiTenancy\TenantContextInterface;
use Nexara\ApiPlatformVoter\MultiTenancy\TenantInterface;
use Symfony\Bundle\SecurityBundle\Security;

final class TenantContext implements TenantContextInterface
{
    private ?TenantInterface $currentTenant = null;

    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function getCurrentTenant(): ?TenantInterface
    {
        if ($this->currentTenant === null) {
            $user = $this->security->getUser();
            
            if ($user && method_exists($user, 'getTenant')) {
                $this->currentTenant = $user->getTenant();
            }
        }

        return $this->currentTenant;
    }

    public function setCurrentTenant(?TenantInterface $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    public function hasTenant(): bool
    {
        return $this->getCurrentTenant() !== null;
    }
}
```

## 4. Configure Services

```yaml
# config/services.yaml
services:
    App\Security\TenantContext:
        public: true

    Nexara\ApiPlatformVoter\MultiTenancy\TenantContextInterface:
        alias: App\Security\TenantContext
```

## 5. Create Tenant-Aware Voter

```php
// src/Security/Voter/ArticleVoter.php
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

    protected function canList(): bool
    {
        // List is filtered by tenant in query
        return $this->isAuthenticated();
    }

    protected function canRead(mixed $object): bool
    {
        // Can only read articles from own tenant
        return $this->belongsToCurrentTenant($object);
    }

    protected function canCreate(mixed $object): bool
    {
        // Can create if authenticated
        return $this->isAuthenticated();
    }

    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        // Must belong to current tenant
        if (!$this->belongsToCurrentTenant($object)) {
            return false;
        }

        // And user must be owner or admin
        return $this->isOwner($object, $this->security->getUser())
            || $this->security->isGranted('ROLE_ADMIN');
    }

    protected function canDelete(mixed $object): bool
    {
        // Must belong to current tenant
        if (!$this->belongsToCurrentTenant($object)) {
            return false;
        }

        // Only admin can delete
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function isAuthenticated(): bool
    {
        return $this->security->getUser() !== null;
    }
}
```

## 6. Add Doctrine Filter

```php
// src/Doctrine/Filter/TenantFilter.php
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

final class TenantFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if (!$targetEntity->reflClass->implementsInterface(TenantAwareInterface::class)) {
            return '';
        }

        $tenantId = $this->getParameter('tenant_id');
        
        return sprintf('%s.tenant_id = %s', $targetTableAlias, $tenantId);
    }
}
```

## 7. Configure Doctrine Filter

```yaml
# config/packages/doctrine.yaml
doctrine:
    orm:
        filters:
            tenant_filter:
                class: App\Doctrine\Filter\TenantFilter
                enabled: true
```

## 8. Set Filter Parameters

```php
// src/EventListener/TenantFilterListener.php
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class TenantFilterListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $tenant = $this->tenantContext->getCurrentTenant();
        
        if ($tenant === null) {
            return;
        }

        $filter = $this->entityManager->getFilters()->enable('tenant_filter');
        $filter->setParameter('tenant_id', $tenant->getId());
    }
}
```

## 9. Automatically Set Tenant on Create

```php
// src/State/Processor/TenantAwareProcessor.php
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;

final class TenantAwareProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $decorated,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof TenantAwareInterface && $data->getTenant() === null) {
            $tenant = $this->tenantContext->getCurrentTenant();
            
            if ($tenant !== null) {
                $data->setTenant($tenant);
            }
        }

        return $this->decorated->process($data, $operation, $uriVariables, $context);
    }
}
```

## 10. Testing Multi-Tenancy

```php
use Nexara\ApiPlatformVoter\Testing\VoterTestCase;

final class ArticleVoterMultiTenancyTest extends VoterTestCase
{
    private TenantContext $tenantContext;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenantContext = new TenantContext($this->security);
    }

    protected function createVoter(): ArticleVoter
    {
        return new ArticleVoter($this->security, $this->tenantContext);
    }

    public function testCannotReadArticleFromDifferentTenant(): void
    {
        $tenant1 = $this->createTenant(1, 'tenant1');
        $tenant2 = $this->createTenant(2, 'tenant2');

        $this->tenantContext->setCurrentTenant($tenant1);
        
        $article = new Article();
        $article->setTenant($tenant2);

        $this->assertVoterDenies('article:read', $article);
    }

    public function testCanReadArticleFromSameTenant(): void
    {
        $tenant = $this->createTenant(1, 'tenant1');

        $this->tenantContext->setCurrentTenant($tenant);
        
        $article = new Article();
        $article->setTenant($tenant);

        $this->assertVoterGrants('article:read', $article);
    }

    private function createTenant(int $id, string $identifier): Tenant
    {
        $tenant = new Tenant();
        $tenant->setId($id);
        $tenant->setIdentifier($identifier);
        
        return $tenant;
    }
}
```

## Best Practices

1. **Always Filter Queries**: Use Doctrine filters to prevent cross-tenant data leaks
2. **Set Tenant Automatically**: Use processors to set tenant on create
3. **Validate Tenant**: Always validate tenant in voters
4. **Test Isolation**: Thoroughly test tenant isolation
5. **Audit Logging**: Log tenant-related access attempts
6. **Super Admin**: Consider a super admin role that can access all tenants
