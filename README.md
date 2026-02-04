# Nexara API Platform Voter

[![Packagist Version](https://img.shields.io/packagist/v/nexara/api-platform-voter.svg)](https://packagist.org/packages/nexara/api-platform-voter)
[![Packagist Downloads](https://img.shields.io/packagist/dt/nexara/api-platform-voter.svg)](https://packagist.org/packages/nexara/api-platform-voter)
[![License](https://img.shields.io/packagist/l/nexara/api-platform-voter.svg)](https://github.com/nexara-group/api-platform-voter/blob/main/LICENSE)

A Symfony bundle that enforces consistent voter-based authorization for API Platform 3 resources.

## Features

### Core Features
- ✅ **Opt-in security** per resource via `#[Secured]` attribute
- ✅ **Automatic CRUD mapping** to voter attributes (`{prefix}:list`, `{prefix}:create`, etc.)
- ✅ **Custom operation support** with explicit voter methods
- ✅ **UPDATE operations** receive both new and previous objects for comparison
- ✅ **Flexible configuration** with customizable prefixes and targeted voters
- ✅ **Type-safe** with PHP 8.1+ and strict types
- ✅ **Well-tested** with comprehensive test coverage

### Advanced Features (v0.3+)
- 🧪 **Testing utilities** with role hierarchy support (`VoterTestTrait`, `SecurityBuilder`)
- ⚙️ **Flexible operation mapping** with configurable naming conventions
- 🔒 **Automatic custom provider security** with opt-in/opt-out configuration
- 🐛 **Debug tools** with voter chain visualization
- 📊 **Validation commands** for voter implementations
- 🔄 **Migration helpers** from native API Platform security
- 🌐 **GraphQL support** with field-level authorization
- 🏢 **Multi-tenancy** with automatic tenant context injection
- ⚡ **Performance optimizations** with lazy loading and caching
- 🛠️ **Maker command** with pre-defined templates

## Requirements

- PHP 8.1 or higher
- Symfony 6.4 or 7.0+
- API Platform 3.0+

## Installation

```bash
composer require nexara/api-platform-voter
```

The bundle will be automatically registered in `config/bundles.php`.

## Quick Start

### 1. Mark Your Resource as Protected

Add the `#[Secured]` attribute to your API Platform resource:

```php
use ApiPlatform\Metadata\ApiResource;
use Nexara\ApiPlatformVoter\Attribute\Secured;

#[ApiResource]
#[Secured(prefix: 'article', voter: ArticleVoter::class)]
class Article
{
    // Your entity properties...
}
```

### 2. Create a Voter

Use the maker command to generate a voter:

```bash
php bin/console make:api-resource-voter
```

Or create one manually with **3 configuration modes** (v0.3+):

#### Mode 1: Auto-Configuration (Recommended)

```php
namespace App\Security\Voter;

use App\Entity\Article;
use Nexara\ApiPlatformVoter\Voter\CrudVoter;
use Symfony\Bundle\SecurityBundle\Security;

final class ArticleVoter extends CrudVoter
{
    public function __construct(private readonly Security $security)
    {
        $this->autoConfigure(); // ✨ Zero config!
    }

    protected function canCreate(): bool
    {
        return $this->security->isGranted('ROLE_USER');
    }

    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        return $object->getAuthor() === $this->security->getUser();
    }

    protected function canDelete(mixed $object): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }
}
```

#### Mode 2: Fluent Builder (Modern)

```php
final class ArticleVoter extends CrudVoter
{
    public function __construct(private readonly Security $security)
    {
        $this->configure()
            ->prefix('article')
            ->resource(Article::class)
            ->autoDiscoverOperations(); // Auto-finds can* methods
    }

    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        return $object->getAuthor() === $this->security->getUser();
    }
}
```

#### Mode 3: Manual (Backward Compatible)

```php
final class ArticleVoter extends CrudVoter
{
    public function __construct(private readonly Security $security)
    {
        $this->setPrefix('article');
        $this->setResourceClasses(Article::class);
    }

    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        return $object->getAuthor() === $this->security->getUser();
    }
}
```

### 3. That's It!

Your API Platform resource is now protected by the voter. All CRUD operations will be automatically checked.

## Operation Mapping

The bundle automatically maps API Platform operations to voter attributes:

| Operation | HTTP Method | Voter Attribute | Voter Method | Subject |
|-----------|-------------|-----------------|--------------|---------|
| Collection GET | `GET /articles` | `article:list` | `canList()` | `null` |
| Collection POST | `POST /articles` | `article:create` | `canCreate()` | New object |
| Item GET | `GET /articles/{id}` | `article:read` | `canRead($object)` | Object |
| Item PUT/PATCH | `PUT /articles/{id}` | `article:update` | `canUpdate($new, $previous)` | `[$new, $previous]` |
| Item DELETE | `DELETE /articles/{id}` | `article:delete` | `canDelete($object)` | Object |
| Custom operation | `POST /articles/{id}/publish` | `article:publish` | `canPublish($object, $previous)` | Object or `[$new, $previous]` |

## Custom Operations

For custom operations, implement a method following the naming convention `can{OperationName}`:

```php
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/articles/{id}/publish',
            name: 'publish',
            // ... other config
        ),
    ]
)]
#[Secured(voter: ArticleVoter::class)]
class Article
{
    // ...
}
```

```php
final class ArticleVoter extends CrudVoter
{
    // ... other methods

    protected function canPublish(mixed $object, mixed $previousObject): bool
    {
        // Custom logic for publish operation
        return $this->security->isGranted('ROLE_MODERATOR')
            && $object->getStatus() === 'draft';
    }
}
```

## Voter Configuration Modes (v0.3+)

The unified `CrudVoter` supports 3 configuration modes:

### 1. Auto-Configuration (Zero Config)

```php
final class ArticleVoter extends CrudVoter
{
    public function __construct(private readonly Security $security)
    {
        $this->autoConfigure(); // Reads from #[Secured] + VoterRegistry
    }
}
```

### 2. Fluent Builder (Modern API)

```php
final class ArticleVoter extends CrudVoter
{
    public function __construct(private readonly Security $security)
    {
        $this->configure()
            ->prefix('article')
            ->resource(Article::class)
            ->operations('publish', 'archive')
            ->autoDiscoverOperations(); // Auto-finds can* methods
    }
}
```

### 3. Manual Configuration (Backward Compatible)

```php
final class ArticleVoter extends CrudVoter
{
    public function __construct(private readonly Security $security)
    {
        $this->setPrefix('article');
        $this->setResourceClasses(Article::class);
    }
}
```

> **Migration from v0.2.x:** See [VOTER_MIGRATION_GUIDE.md](VOTER_MIGRATION_GUIDE.md)

## Configuration

Create `config/packages/nexara_api_platform_voter.yaml`:

```yaml
nexara_api_platform_voter:
    # Enable/disable the bundle (default: true)
    enabled: true
    
    # Enforce authorization for collection list operations (default: true)
    enforce_collection_list: true
    
    # Custom providers security (v0.3+)
    custom_providers:
        auto_secure: true  # Automatically secure all custom providers
        secure: []         # Explicitly secure specific providers
        skip: []           # Skip specific providers
    
    # Operation mapping configuration (v0.3+)
    operation_mapping:
        custom_operation_patterns:
            - '!^_api_'  # Exclude _api_* operations
        naming_convention: 'preserve'  # snake_case, camelCase, kebab-case, preserve
        normalize_names: false
        detect_by_uri: true  # Detect custom ops by URI pattern
    
    # Debug mode (v0.3+)
    debug: false
    debug_output: 'detailed'  # simple, detailed, json
    
    # Audit logging (v0.3+)
    audit:
        enabled: false
        level: 'all'  # all, denied_only, granted_only
        include_context: true
```

## Attribute Options

### `#[Secured]` Parameters

- **`prefix`** (optional): Custom prefix for voter attributes. Defaults to lowercase resource class name.
- **`voter`** (optional): Specific voter class to use. When set, only this voter can grant access.

```php
#[Secured(
    prefix: 'blog_post',
    voter: BlogPostVoter::class
)]
class Article { }
```

## Advanced Usage

### Accessing the User

Inject Symfony's `Security` service to access the current user:

```php
public function __construct(
    private readonly Security $security,
) {
    $this->setPrefix('article');
    $this->setResourceClasses(Article::class);
}

protected function canUpdate(mixed $object, mixed $previousObject): bool
{
    $user = $this->security->getUser();
    return $user && $object->getAuthor() === $user;
}
```

### Comparing Previous and New Objects

For UPDATE operations, you receive both the new and previous state:

```php
protected function canUpdate(mixed $object, mixed $previousObject): bool
{
    // Prevent changing the author
    if ($object->getAuthor() !== $previousObject->getAuthor()) {
        return $this->security->isGranted('ROLE_ADMIN');
    }
    
    return $object->getAuthor() === $this->security->getUser();
}
```

### Multiple Resource Classes

A single voter can handle multiple resource classes:

```php
public function __construct()
{
    $this->setPrefix('content');
    $this->setResourceClasses(Article::class, BlogPost::class, Page::class);
}
```

### GraphQL Support

For GraphQL APIs, use `GraphQLCrudVoter` with field-level authorization:

```php
use Nexara\ApiPlatformVoter\GraphQL\GraphQLCrudVoter;

final class ArticleVoter extends GraphQLCrudVoter
{
    protected function canAccessField(string $fieldName, mixed $object): bool
    {
        return match ($fieldName) {
            'email' => $this->security->isGranted('ROLE_ADMIN'),
            'internalNotes' => $object->getAuthor() === $this->security->getUser(),
            default => true,
        };
    }
    
    protected function canModifyField(string $fieldName, mixed $object, mixed $newValue): bool
    {
        return match ($fieldName) {
            'author' => $this->security->isGranted('ROLE_ADMIN'),
            'publishedAt' => $this->security->isGranted('ROLE_MODERATOR'),
            default => true,
        };
    }
}
```

### Multi-Tenancy

For multi-tenant applications, use `TenantAwareVoterTrait`:

```php
use Nexara\ApiPlatformVoter\Voter\CrudVoter;
use Nexara\ApiPlatformVoter\MultiTenancy\TenantAwareVoterTrait;

final class ArticleVoter extends CrudVoter
{
    use TenantAwareVoterTrait;
    
    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        // TenantContext is automatically injected
        if (!$this->belongsToCurrentTenant($object)) {
            return false;
        }
        
        return $object->getAuthor() === $this->security->getUser();
    }
}
```

### Debug & Troubleshooting

Visualize voter decision chains:

```php
use Nexara\ApiPlatformVoter\Debug\VoterChainVisualizer;

$visualizer = new VoterChainVisualizer($debugger);

// Text visualization
echo $visualizer->visualize('article:update');

// Tree visualization
echo $visualizer->visualizeAsTree('article:update');

// Summary
echo $visualizer->summarize('article:update');
```

Enable debug mode in configuration:

```yaml
nexara_api_platform_voter:
    debug: true
    debug_output: 'detailed'
```

## Testing

### Testing Your Voters

The bundle provides powerful testing utilities with full role hierarchy support:

#### Using VoterTestTrait

```php
use Nexara\ApiPlatformVoter\Testing\VoterTestTrait;
use PHPUnit\Framework\TestCase;

class ArticleVoterTest extends TestCase
{
    use VoterTestTrait;
    
    public function testModeratorCanPublish(): void
    {
        $user = $this->createUser(['ROLE_MODERATOR']);
        
        // Creates Security with proper role hierarchy
        $security = $this->createSecurityWithRoleHierarchy([
            'ROLE_ADMIN' => ['ROLE_MODERATOR', 'ROLE_USER'],
            'ROLE_MODERATOR' => ['ROLE_USER'],
        ], $user);
        
        $voter = new ArticleVoter($security);
        
        // Now $security->isGranted('ROLE_USER') returns true for MODERATOR
        $article = new Article();
        $this->assertTrue($voter->canPublish($article, null));
    }
}
```

#### Using SecurityBuilder

```php
use Nexara\ApiPlatformVoter\Testing\SecurityBuilder;

$security = SecurityBuilder::create()
    ->withRoleHierarchy([
        'ROLE_ADMIN' => ['ROLE_MODERATOR', 'ROLE_USER'],
        'ROLE_MODERATOR' => ['ROLE_USER'],
    ])
    ->withUser($user)
    ->build();

$voter = new ArticleVoter($security);
```

#### Using VoterTestCase

```php
use Nexara\ApiPlatformVoter\Testing\VoterTestCase;

class ArticleVoterTest extends VoterTestCase
{
    protected function createVoter(): VoterInterface
    {
        return new ArticleVoter($this->createMock(Security::class));
    }
    
    public function testGrantsAccess(): void
    {
        $this->mockUser(['ROLE_USER']);
        $this->assertVoterGrants('article:create', new Article());
    }
    
    public function testDeniesAccess(): void
    {
        $this->mockAnonymousUser();
        $this->assertVoterDenies('article:delete', new Article());
    }
}
```

### Running Tests

The bundle includes a comprehensive test suite:

```bash
# Run tests
composer test

# Run all quality checks
composer qa
```

## Console Commands

### Validate Voter Implementations

```bash
# Validate all voters
php bin/console voter:validate

# Validate specific voter
php bin/console voter:validate --voter=App\\Voter\\ArticleVoter

# Show detailed output
php bin/console voter:validate --detailed
```

Validates:
- ✅ CRUD method implementations
- ✅ Custom operation methods
- ✅ VoterRegistry registration
- ✅ `#[Secured]` attribute on resources
- ✅ Test coverage
- ✅ Method signatures

### Analyze Migration from Native Security

```bash
php bin/console voter:analyze-migration
```

Provides:
- 📊 Analysis of resources with native security expressions
- 📋 Step-by-step migration plan
- ⏱️ Estimated migration time
- 🎯 Complexity assessment

## Quality Assurance

This bundle maintains high code quality standards:

- **PHPStan** (level 8) for static analysis
- **ECS** for code style (PSR-12, Clean Code)
- **Rector** for automated refactoring
- **PHPUnit** for testing

```bash
composer phpstan      # Static analysis
composer ecs          # Check code style
composer ecs-fix      # Fix code style
composer rector       # Check refactoring opportunities
composer test         # Run tests
composer qa           # Run all checks
```

## Contributing

Contributions are welcome! Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover a security vulnerability, please review our [Security Policy](SECURITY.md).

## License

This bundle is released under the [MIT License](LICENSE).

## Credits

Developed and maintained by [Nexara s.r.o.](https://github.com/nexara-group)

## Support

- 📖 [Documentation](https://github.com/nexara-group/api-platform-voter)
- 🐛 [Issue Tracker](https://github.com/nexara-group/api-platform-voter/issues)
- 💬 [Discussions](https://github.com/nexara-group/api-platform-voter/discussions)
