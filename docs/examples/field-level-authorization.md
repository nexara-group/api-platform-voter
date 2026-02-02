# Field-Level Authorization

This guide shows how to implement field-level authorization to control access to specific entity properties.

## Basic Field-Level Authorization

```php
use Nexara\ApiPlatformVoter\Security\Voter\CrudVoter;
use Symfony\Bundle\SecurityBundle\Security;

final class ArticleVoter extends CrudVoter implements FieldLevelVoterInterface
{
    public function __construct(
        private readonly Security $security,
    ) {
        $this->setPrefix('article');
        $this->setResourceClasses(Article::class);
    }

    public function canAccessField(string $fieldName, mixed $object): bool
    {
        return match ($fieldName) {
            // Public fields - everyone can access
            'id', 'title', 'publishedAt' => true,
            
            // Sensitive fields - only admin
            'authorEmail', 'internalNotes' => $this->security->isGranted('ROLE_ADMIN'),
            
            // Draft content - only owner or editor
            'draftContent' => $this->canAccessDraftContent($object),
            
            // Default - allow access
            default => true,
        };
    }

    private function canAccessDraftContent(mixed $object): bool
    {
        $user = $this->security->getUser();
        
        if (!$user) {
            return false;
        }
        
        // Owner can access
        if ($object->getAuthor() === $user) {
            return true;
        }
        
        // Editors and admins can access
        return $this->security->isGranted('ROLE_EDITOR') 
            || $this->security->isGranted('ROLE_ADMIN');
    }
}
```

## Context-Based Field Access

```php
use Nexara\ApiPlatformVoter\Security\Context\RequestContextFactory;

final class UserVoter extends CrudVoter implements FieldLevelVoterInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly RequestContextFactory $contextFactory,
    ) {
        $this->setPrefix('user');
        $this->setResourceClasses(User::class);
    }

    public function canAccessField(string $fieldName, mixed $object): bool
    {
        $context = $this->contextFactory->create();
        
        return match ($fieldName) {
            // Email visible to authenticated users
            'email' => $this->security->getUser() !== null,
            
            // Phone number only from internal network
            'phoneNumber' => $context && $context->isIpInRange('10.0.0.0/8'),
            
            // SSN only to admins during business hours
            'ssn' => $this->canAccessSSN($context),
            
            default => true,
        };
    }

    private function canAccessSSN(?RequestContext $context): bool
    {
        if (!$this->security->isGranted('ROLE_ADMIN')) {
            return false;
        }
        
        if (!$context || !$context->isBusinessHours()) {
            return false;
        }
        
        return true;
    }
}
```

## Serialization Context Provider

Create a serialization context provider that uses field-level authorization:

```php
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class FieldLevelSerializationContextBuilder implements SerializerContextBuilderInterface
{
    public function __construct(
        private readonly SerializerContextBuilderInterface $decorated,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly VoterRegistry $voterRegistry,
    ) {
    }

    public function createFromRequest(Request $request, bool $normalization, ?array $extractedAttributes = null): array
    {
        $context = $this->decorated->createFromRequest($request, $normalization, $extractedAttributes);
        
        if (!$normalization) {
            return $context;
        }
        
        $resourceClass = $context['resource_class'] ?? null;
        if (!$resourceClass) {
            return $context;
        }
        
        $voter = $this->voterRegistry->getVoterClass($resourceClass);
        if (!$voter instanceof FieldLevelVoterInterface) {
            return $context;
        }
        
        $object = $context['object_to_populate'] ?? null;
        if (!$object) {
            return $context;
        }
        
        // Filter fields based on authorization
        $allowedFields = $this->getAuthorizedFields($voter, $object);
        $context['groups'] = array_intersect($context['groups'] ?? [], $allowedFields);
        
        return $context;
    }

    private function getAuthorizedFields(FieldLevelVoterInterface $voter, object $object): array
    {
        $reflection = new \ReflectionClass($object);
        $allowedFields = [];
        
        foreach ($reflection->getProperties() as $property) {
            if ($voter->canAccessField($property->getName(), $object)) {
                $allowedFields[] = $property->getName();
            }
        }
        
        return $allowedFields;
    }
}
```

## API Resource Configuration

Configure your API resource with serialization groups:

```php
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['article:read']],
)]
class Article
{
    #[Groups(['article:read'])]
    public int $id;

    #[Groups(['article:read'])]
    public string $title;

    #[Groups(['article:read', 'article:admin'])]
    public string $authorEmail;

    #[Groups(['article:read', 'article:draft'])]
    public ?string $draftContent = null;

    #[Groups(['article:admin'])]
    public ?string $internalNotes = null;
}
```

## Testing Field-Level Authorization

```php
use Nexara\ApiPlatformVoter\Testing\VoterTestCase;

final class ArticleVoterFieldLevelTest extends VoterTestCase
{
    protected function createVoter(): ArticleVoter
    {
        return new ArticleVoter($this->security);
    }

    public function testPublicFieldsAreAccessible(): void
    {
        $this->mockAnonymousUser();
        $article = new Article();
        
        $voter = $this->voter;
        $this->assertTrue($voter->canAccessField('id', $article));
        $this->assertTrue($voter->canAccessField('title', $article));
    }

    public function testSensitiveFieldsRequireAdmin(): void
    {
        $this->mockUser(['ROLE_USER']);
        $article = new Article();
        
        $voter = $this->voter;
        $this->assertFalse($voter->canAccessField('authorEmail', $article));
        $this->assertFalse($voter->canAccessField('internalNotes', $article));
    }

    public function testAdminCanAccessSensitiveFields(): void
    {
        $this->mockUser(['ROLE_ADMIN']);
        $article = new Article();
        
        $voter = $this->voter;
        $this->assertTrue($voter->canAccessField('authorEmail', $article));
        $this->assertTrue($voter->canAccessField('internalNotes', $article));
    }

    public function testOwnerCanAccessDraftContent(): void
    {
        $user = $this->mockUser(['ROLE_USER'], 'owner@example.com', ['getId' => 1]);
        
        $article = new Article();
        $article->setAuthor($user);
        
        $voter = $this->voter;
        $this->assertTrue($voter->canAccessField('draftContent', $article));
    }
}
```

## Best Practices

1. **Granular Control**: Define field access at the finest level needed
2. **Default Deny**: Start with denying access and explicitly allow
3. **Performance**: Cache field authorization results when possible
4. **Groups**: Use Symfony serialization groups for better control
5. **Documentation**: Document which fields are restricted and why
6. **Testing**: Test all field-level authorization scenarios
