<?php

declare(strict_types=1);

namespace Nexara\ApiPlatformVoter\Tests\Security\Voter;

use Nexara\ApiPlatformVoter\Security\Voter\CrudVoter;
use Nexara\ApiPlatformVoter\Testing\VoterTestCase;

final class CrudVoterIntegrationTest extends VoterTestCase
{
    public function testListOperationIsGrantedByDefault(): void
    {
        $this->mockAnonymousUser();
        $this->assertVoterGrants('article:list', Article::class);
    }

    public function testCreateOperationRequiresAuthentication(): void
    {
        $this->mockAnonymousUser();
        $this->assertVoterDenies('article:create', new Article());
    }

    public function testCreateOperationGrantedForAuthenticatedUser(): void
    {
        $this->mockUser(['ROLE_USER']);
        $this->assertVoterGrants('article:create', new Article());
    }

    public function testReadOperationIsPublic(): void
    {
        $this->mockAnonymousUser();
        $article = new Article();
        $this->assertVoterGrants('article:read', $article);
    }

    public function testUpdateOperationRequiresOwnership(): void
    {
        $user = $this->mockUser(['ROLE_USER'], 'user@example.com', [
            'getId' => 1,
        ]);

        $article = new Article();
        $article->authorId = 2;

        $this->assertVoterDenies('article:update', [$article, $article]);
    }

    public function testUpdateOperationGrantedForOwner(): void
    {
        $user = $this->mockUser(['ROLE_USER'], 'user@example.com', [
            'getId' => 1,
        ]);

        $article = new Article();
        $article->authorId = 1;

        $this->assertVoterGrants('article:update', [$article, $article]);
    }

    public function testDeleteOperationRequiresAdminRole(): void
    {
        $this->mockUser(['ROLE_USER']);
        $article = new Article();
        $this->assertVoterDenies('article:delete', $article);
    }

    public function testDeleteOperationGrantedForAdmin(): void
    {
        $this->mockUser(['ROLE_ADMIN']);
        $article = new Article();
        $this->assertVoterGrants('article:delete', $article);
    }

    public function testCustomOperationSupported(): void
    {
        $this->mockUser(['ROLE_USER']);
        $article = new Article();
        $this->assertVoterSupports('article:publish', $article);
    }

    public function testUnsupportedAttributeAbstains(): void
    {
        $article = new Article();
        $this->assertVoterAbstains('article:unknown', $article);
    }

    protected function createVoter(): CrudVoter
    {
        return new TestArticleVoter();
    }
}

final class TestArticleVoter extends CrudVoter
{
    public function __construct()
    {
        $this->setPrefix('article');
        $this->setResourceClasses(Article::class);
        $this->customOperations = ['publish'];
    }

    protected function canList(): bool
    {
        return true;
    }

    protected function canCreate(mixed $object): bool
    {
        return $this->token->getUser() !== null;
    }

    protected function canRead(mixed $object): bool
    {
        return true;
    }

    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        $user = $this->token->getUser();

        if ($user === null) {
            return false;
        }

        if (! method_exists($user, 'getId')) {
            return false;
        }

        return $object->authorId === $user->getId();
    }

    protected function canDelete(mixed $object): bool
    {
        $user = $this->token->getUser();

        if ($user === null) {
            return false;
        }

        return in_array('ROLE_ADMIN', $user->getRoles(), true);
    }

    protected function canCustomOperation(string $operation, mixed $object, mixed $previousObject): bool
    {
        return $operation === 'publish';
    }
}

final class Article
{
    public int $authorId = 0;
}
