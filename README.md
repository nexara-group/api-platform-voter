# Nexara API Platform Voter

Symfony bundle that enforces a consistent voter-based authorization standard for API Platform 3.

## Key ideas

- Opt-in per resource via `#[ApiPlatformVoterProtected]`.
- CRUD operations are mapped to namespaced voter attributes: `{prefix}:{operation}`.
- Custom operations must be explicitly allowed in the concrete voter.
- UPDATE subjects are passed as `[newObject, previousObject]`.

## Installation

```bash
composer require nexara/api-platform-voter
```

## Usage

### 1) Mark resource as protected

```php
use ApiPlatform\Metadata\ApiResource;
use Nexara\ApiPlatformVoter\Attribute\ApiPlatformVoterProtected;

#[ApiResource]
#[ApiPlatformVoterProtected(prefix: 'video', voter: VideoVoter::class)]
final class Video
{
}
```

### 2) Implement a voter

```php
use Nexara\ApiPlatformVoter\Security\Voter\CrudVoter;

final class VideoVoter extends CrudVoter
{
    public function __construct()
    {
        $this->setPrefix('video');
        $this->setResourceClasses(Video::class);

        $this->customOperations = [
            'publish',
        ];
    }

    protected function canRead(mixed $object): bool
    {
        // ...
        return true;
    }

    protected function canCustomOperation(string $operation, mixed $object, mixed $previousObject): bool
    {
        if ($operation === 'publish') {
            // ...
            return true;
        }

        return false;
    }
}
```

### 3) What attributes are checked

- `GET collection` -> `{prefix}:list`
- `POST collection` -> `{prefix}:create`
- `GET item` -> `{prefix}:read`
- `PUT/PATCH item` -> `{prefix}:update` with subject `[new, previous]`
- `DELETE item` -> `{prefix}:delete`
- custom operation -> `{prefix}:{operationName}`

## Attribute options

- `prefix`: overrides the default prefix (otherwise resource short class name is used, lowercased)
- `voter`: when set, the authorization check will be wrapped so that only this voter class can decide

## Configuration

```yaml
# config/packages/nexara_api_platform_voter.yaml
nexara_api_platform_voter:
  enabled: true
  enforce_collection_list: true
```
