<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace ?>;

use <?= $voter_class ?>;
use <?= $resource_class ?>;
use Nexara\ApiPlatformVoter\Testing\VoterTestCase;

final class <?= $class_name ?> extends VoterTestCase
{
    protected function createVoter(): <?= $voter_class_short ?>
    {
        return new <?= $voter_class_short ?>();
    }

    public function testListOperationIsAccessible(): void
    {
        $this->mockAnonymousUser();
        $this->assertVoterGrants('<?= $prefix ?>:list', <?= $resource_class_short ?>::class);
    }

    public function testReadOperationIsAccessible(): void
    {
        $object = new <?= $resource_class_short ?>();
        $this->assertVoterGrants('<?= $prefix ?>:read', $object);
    }

    public function testCreateRequiresAuthentication(): void
    {
        $this->mockAnonymousUser();
        $object = new <?= $resource_class_short ?>();
        $this->assertVoterDenies('<?= $prefix ?>:create', $object);
    }

    public function testAuthenticatedUserCanCreate(): void
    {
        $this->mockUser(['ROLE_USER']);
        $object = new <?= $resource_class_short ?>();
        $this->assertVoterGrants('<?= $prefix ?>:create', $object);
    }

    public function testUpdateRequiresOwnership(): void
    {
        $this->mockUser(['ROLE_USER'], 'user@example.com', ['getId' => 1]);
        
        $object = new <?= $resource_class_short ?>();
        // TODO: Set object properties to test ownership
        
        $this->assertVoterDenies('<?= $prefix ?>:update', [$object, $object]);
    }

    public function testDeleteRequiresAdminRole(): void
    {
        $this->mockUser(['ROLE_USER']);
        $object = new <?= $resource_class_short ?>();
        $this->assertVoterDenies('<?= $prefix ?>:delete', $object);
    }

    public function testAdminCanDelete(): void
    {
        $this->mockUser(['ROLE_ADMIN']);
        $object = new <?= $resource_class_short ?>();
        $this->assertVoterGrants('<?= $prefix ?>:delete', $object);
    }

<?php foreach ($custom_operations as $operation):
    $camelCased = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $operation)));
    ?>
    public function testCustomOperation<?= $camelCased ?>(): void
    {
        $this->mockUser(['ROLE_USER']);
        $object = new <?= $resource_class_short ?>();
        
        // TODO: Implement test logic for <?= $operation ?> operation
        $this->assertVoterSupports('<?= $prefix ?>:<?= $operation ?>', $object);
    }

<?php endforeach; ?>
    public function testUnsupportedAttributeAbstains(): void
    {
        $object = new <?= $resource_class_short ?>();
        $this->assertVoterAbstains('<?= $prefix ?>:unsupported', $object);
    }
}
