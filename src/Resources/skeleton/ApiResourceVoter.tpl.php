<?php

declare(strict_types=1);

namespace <?= $namespace ?>;

use Nexara\ApiPlatformVoter\Security\Voter\CrudVoter;

final class <?= $class_name ?> extends CrudVoter
{
    public function __construct()
    {
        $this->setResourceClasses(<?= $resource_class ?>::class);

<?php if ($custom_operations !== []) { ?>
        $this->customOperations = [
<?php foreach ($custom_operations as $op) { ?>
            '<?= $op ?>',
<?php } ?>
        ];
<?php } ?>
    }

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

    protected function canCustomOperation(string $operation, mixed $object, mixed $previousObject): bool
    {
<?php if ($custom_operations === []) { ?>
        return false;
<?php } else { ?>
        return match ($operation) {
<?php foreach ($custom_operations as $op) { ?>
            '<?= $op ?>' => false,
<?php } ?>
            default => false,
        };
<?php } ?>
    }
}
