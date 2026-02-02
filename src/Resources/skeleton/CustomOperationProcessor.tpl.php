<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace ?>;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use <?= $resource_class ?>;

final class <?= $class_name ?> implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (! $data instanceof <?= $resource_class_short ?>) {
            throw new \InvalidArgumentException(sprintf(
                'Expected instance of %s, got %s',
                <?= $resource_class_short ?>::class,
                get_debug_type($data)
            ));
        }

        // TODO: Implement <?= $operation_name ?> operation logic
        // Example:
        // $data->setStatus('<?= $operation_name ?>');
        // $this->entityManager->flush();

        return $data;
    }
}
