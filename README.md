# Nexara API Platform Voter

[![Packagist Version](https://img.shields.io/packagist/v/nexara/api-platform-voter.svg)](https://packagist.org/packages/nexara/api-platform-voter)
[![Packagist Downloads](https://img.shields.io/packagist/dt/nexara/api-platform-voter.svg)](https://packagist.org/packages/nexara/api-platform-voter)
[![License](https://img.shields.io/packagist/l/nexara/api-platform-voter.svg)](https://github.com/nexara-group/api-platform-voter/blob/main/LICENSE)

A Symfony bundle that enforces consistent voter-based authorization for API Platform 3 resources.

## Features

- ✅ **Opt-in security** per resource via `#[ApiResourceVoter]` attribute
- ✅ **Automatic CRUD mapping** to voter attributes (`{prefix}:list`, `{prefix}:create`, etc.)
- ✅ **Custom operation support** with explicit voter methods
- ✅ **UPDATE operations** receive both new and previous objects for comparison
- ✅ **Flexible configuration** with customizable prefixes and targeted voters
- ✅ **Performance optimized** with metadata caching
- ✅ **Type-safe** with PHP 8.1+ and strict types
- ✅ **Well-tested** with comprehensive test coverage
- ✅ **Maker command** for generating voter classes

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

Add the `#[ApiResourceVoter]` attribute to your API Platform resource:

```php
use ApiPlatform\Metadata\ApiResource;
use Nexara\ApiPlatformVoter\Attribute\ApiResourceVoter;

#[ApiResource]
#[ApiResourceVoter(prefix: 'article', voter: ArticleVoter::class)]
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

Or create one manually:

```php
namespace App\Security\Voter;

use App\Entity\Article;
use Nexara\ApiPlatformVoter\Security\Voter\CrudVoter;
use Symfony\Bundle\SecurityBundle\Security;

final class ArticleVoter extends CrudVoter
{
    public function __construct(
        private readonly Security $security,
    ) {
        $this->setPrefix('article');
        $this->setResourceClasses(Article::class);
    }

    protected function canList(): bool
    {
        return true; // Everyone can list articles
    }

    protected function canCreate(): bool
    {
        return $this->security->isGranted('ROLE_USER');
    }

    protected function canRead(mixed $object): bool
    {
        return true; // Everyone can read articles
    }

    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        // Only the author can update their own articles
        return $object->getAuthor() === $this->security->getUser();
    }

    protected function canDelete(mixed $object): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
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
#[ApiResourceVoter(voter: ArticleVoter::class)]
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

## Auto-Configuration

Use `AutoConfiguredCrudVoter` for automatic setup:

```php
use Nexara\ApiPlatformVoter\Security\Voter\AutoConfiguredCrudVoter;

final class ArticleVoter extends AutoConfiguredCrudVoter
{
    public function __construct(
        private readonly Security $security,
    ) {
        // No need to call setPrefix() or setResourceClasses()
        // They are automatically configured from the resource attribute
    }

    // Implement your authorization methods...
}
```

## Configuration

Create `config/packages/nexara_api_platform_voter.yaml`:

```yaml
nexara_api_platform_voter:
    # Enable/disable the bundle (default: true)
    enabled: true
    
    # Enforce authorization for collection list operations (default: true)
    enforce_collection_list: true
```

## Attribute Options

### `#[ApiResourceVoter]` Parameters

- **`prefix`** (optional): Custom prefix for voter attributes. Defaults to lowercase resource class name.
- **`voter`** (optional): Specific voter class to use. When set, only this voter can grant access.

```php
#[ApiResourceVoter(
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

## Testing

The bundle includes a comprehensive test suite:

```bash
# Run tests
composer test

# Run all quality checks
composer qa
```

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
