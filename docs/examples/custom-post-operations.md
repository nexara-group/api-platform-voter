# Custom POST Operations with read: true

This guide explains how to properly configure custom POST operations with `read: true` for API Platform 3.

## Why read: true?

Custom POST operations that operate on existing resources (like `publish`, `archive`, `approve`) need to load the existing resource first. The `read: true` parameter tells API Platform to load the resource before processing.

## Basic Custom POST Operation

```php
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use Nexara\ApiPlatformVoter\Attribute\ApiResourceVoter;

#[ApiResource(
    operations: [
        // Standard operations
        new Get(),
        new GetCollection(),
        new Post(),
        new Put(),
        new Delete(),
        
        // Custom POST operation
        new Post(
            uriTemplate: '/articles/{id}/publish',
            name: 'publish',
            processor: ArticlePublishProcessor::class,
            read: true,              // Load existing article
            deserialize: false,      // Don't deserialize request body into article
            status: 200,             // Return 200 instead of 201
        ),
    ]
)]
#[ApiResourceVoter(voter: ArticleVoter::class)]
class Article
{
    private int $id;
    private string $title;
    private string $status = 'draft';
    private ?\DateTimeInterface $publishedAt = null;
}
```

## Processor Implementation

```php
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Doctrine\ORM\EntityManagerInterface;

final class ArticlePublishProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Article) {
            throw new \InvalidArgumentException('Expected Article instance');
        }

        // Perform the publish operation
        $data->setStatus('published');
        $data->setPublishedAt(new \DateTimeImmutable());

        $this->entityManager->flush();

        return $data;
    }
}
```

## Voter Configuration

```php
use Nexara\ApiPlatformVoter\Security\Voter\AutoConfiguredCrudVoter;
use Symfony\Bundle\SecurityBundle\Security;

final class ArticleVoter extends AutoConfiguredCrudVoter
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    // Standard CRUD methods...

    protected function canPublish(mixed $object, mixed $previousObject): bool
    {
        // Only allow publishing draft articles
        if ($object->getStatus() !== 'draft') {
            return false;
        }

        // Moderators and admins can publish
        return $this->security->isGranted('ROLE_MODERATOR')
            || $this->security->isGranted('ROLE_ADMIN');
    }
}
```

## Multiple Custom Operations

```php
#[ApiResource(
    operations: [
        // Standard operations
        new Get(),
        new GetCollection(),
        new Post(),
        
        // Custom POST operations
        new Post(
            uriTemplate: '/articles/{id}/publish',
            name: 'publish',
            processor: ArticlePublishProcessor::class,
            read: true,
            deserialize: false,
            status: 200,
        ),
        new Post(
            uriTemplate: '/articles/{id}/archive',
            name: 'archive',
            processor: ArticleArchiveProcessor::class,
            read: true,
            deserialize: false,
            status: 200,
        ),
        new Post(
            uriTemplate: '/articles/{id}/feature',
            name: 'feature',
            processor: ArticleFeatureProcessor::class,
            read: true,
            deserialize: false,
            status: 200,
        ),
    ]
)]
class Article { }
```

## With Request Body

If your custom operation needs to accept data:

```php
new Post(
    uriTemplate: '/articles/{id}/assign',
    name: 'assign',
    processor: ArticleAssignProcessor::class,
    read: true,              // Load existing article
    deserialize: false,      // We'll handle deserialization manually
    input: ArticleAssignInput::class,  // Define input DTO
    status: 200,
)
```

Input DTO:

```php
final class ArticleAssignInput
{
    public function __construct(
        public readonly int $userId,
        public readonly ?string $note = null,
    ) {
    }
}
```

Processor:

```php
final class ArticleAssignProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // $data is the loaded Article
        $input = $context['input'] ?? null;
        
        if (!$input instanceof ArticleAssignInput) {
            throw new \InvalidArgumentException('Invalid input');
        }

        $user = $this->userRepository->find($input->userId);
        $data->assignTo($user, $input->note);

        $this->entityManager->flush();

        return $data;
    }
}
```

## Operation Name Convention

The operation `name` should match the voter method name:

```php
// Operation name: 'publish'
new Post(name: 'publish', ...)

// Voter method: canPublish()
protected function canPublish(mixed $object, mixed $previousObject): bool
```

For operations with hyphens or underscores:

```php
// Operation name: 'publish_draft'
new Post(name: 'publish_draft', ...)

// Voter method: canPublishDraft() (converted to camelCase)
protected function canPublishDraft(mixed $object, mixed $previousObject): bool
```

## Common Patterns

### Status Transition

```php
protected function canPublish(mixed $object, mixed $previousObject): bool
{
    // Check current status
    if ($object->getStatus() !== 'draft') {
        throw AuthorizationException::contextRestriction(
            'article:publish',
            'Can only publish draft articles',
            ['current_status' => $object->getStatus()]
        );
    }

    // Check permissions
    return $this->security->isGranted('ROLE_MODERATOR');
}
```

### Ownership Check

```php
protected function canArchive(mixed $object, mixed $previousObject): bool
{
    $user = $this->security->getUser();
    
    if (!$user) {
        return false;
    }

    // Owner or admin can archive
    return $object->getAuthor() === $user
        || $this->security->isGranted('ROLE_ADMIN');
}
```

### Time-Based Restrictions

```php
protected function canFeature(mixed $object, mixed $previousObject): bool
{
    // Must be published
    if ($object->getStatus() !== 'published') {
        return false;
    }

    // Must be published at least 24 hours ago
    if (!$object->getPublishedAt()) {
        return false;
    }

    $dayAgo = new \DateTimeImmutable('-24 hours');
    if ($object->getPublishedAt() > $dayAgo) {
        return false;
    }

    // Only admins can feature
    return $this->security->isGranted('ROLE_ADMIN');
}
```

## Testing Custom Operations

```php
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ArticleCustomOperationsTest extends WebTestCase
{
    public function testPublishArticle(): void
    {
        $client = static::createClient();
        $this->loginUser($client, 'moderator@example.com', ['ROLE_MODERATOR']);

        $article = $this->createArticle(['status' => 'draft']);

        $client->request('POST', "/api/articles/{$article->getId()}/publish");

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals('published', $data['status']);
    }

    public function testCannotPublishWithoutRole(): void
    {
        $client = static::createClient();
        $this->loginUser($client, 'user@example.com', ['ROLE_USER']);

        $article = $this->createArticle(['status' => 'draft']);

        $client->request('POST', "/api/articles/{$article->getId()}/publish");

        $this->assertResponseStatusCodeSame(403);
    }

    public function testCannotPublishAlreadyPublished(): void
    {
        $client = static::createClient();
        $this->loginUser($client, 'moderator@example.com', ['ROLE_MODERATOR']);

        $article = $this->createArticle(['status' => 'published']);

        $client->request('POST', "/api/articles/{$article->getId()}/publish");

        $this->assertResponseStatusCodeSame(403);
    }
}
```

## Best Practices

1. **Use descriptive names**: Operation names should clearly indicate the action
2. **Set read: true**: Always use `read: true` for operations on existing resources
3. **Use deserialize: false**: Prevent API Platform from deserializing into the loaded entity
4. **Return 200**: Custom operations typically return 200, not 201
5. **Validate state**: Check that the resource is in a valid state for the operation
6. **Clear error messages**: Provide helpful error messages when operations fail
7. **Test thoroughly**: Test all authorization scenarios and state transitions
