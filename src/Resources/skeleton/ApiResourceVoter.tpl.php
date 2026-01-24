<?php declare(strict_types=1);
echo "<?php\n"; ?>

namespace <?php echo $namespace; ?>;

use Nexara\ApiPlatformVoter\Security\Voter\AutoConfiguredCrudVoter;

final class <?php echo $class_name; ?> extends AutoConfiguredCrudVoter
{
    protected function canList(): bool
    {
        return true;
    }

    protected function canCreate(mixed $object): bool
    {
        return true;
    }

    protected function canRead(mixed $object): bool
    {
        return true;
    }

    protected function canUpdate(mixed $object, mixed $previousObject): bool
    {
        return true;
    }

    protected function canDelete(mixed $object): bool
    {
        return true;
    }
<?php if ($custom_operations !== []) { ?>

<?php foreach ($custom_operations as $op) { ?>
    protected function can<?php echo ucfirst($op); ?>(mixed $object, mixed $previousObject): bool
    {
        return false;
    }

<?php } ?>
<?php } ?>
}
