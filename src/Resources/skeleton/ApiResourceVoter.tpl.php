<?php
declare(strict_types=1);

// Helper function to convert operation name to camelCase method name
function toCamelCase(string $str): string
{
    // Replace hyphens and underscores with spaces, then capitalize each word
    $str = str_replace(['-', '_'], ' ', $str);
    $str = ucwords($str);
    // Remove spaces
    $str = str_replace(' ', '', $str);
    return $str;
}

echo "<?php\n";
?>

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
    protected function can<?php echo toCamelCase($op); ?>(mixed $object, mixed $previousObject): bool
    {
        return false;
    }

<?php } ?>
<?php } ?>
}
