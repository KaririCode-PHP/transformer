<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Tests\Support;

use KaririCode\Contract\Inspection\PropertyChangeApplier;
use ReflectionClass;

// ── Support: ReflectionApplier ───────────────────────────────────────
// The kariricode/contract PropertyChangeApplier, implemented for the tests.
// ─────────────────────────────────────────────────────────────────────

/**
 * Builds a copy of the object without calling its constructor, copies
 * every initialized property and writes the changes into the copy, as
 * kariricode/property-inspector's ReflectionChangeApplier does; readonly
 * properties are written once, on the copy.
 */
final class ReflectionApplier implements PropertyChangeApplier
{
    /** @var list<array<string, mixed>> the changes received, in order */
    public private(set) array $applied = [];

    public function applyChanges(object $object, array $changes): object
    {
        $this->applied[] = $changes;
        $class = new ReflectionClass($object);
        $copy = $class->newInstanceWithoutConstructor();

        foreach ($class->getProperties() as $property) {
            if (array_key_exists($property->getName(), $changes)) {
                $property->setValue($copy, $changes[$property->getName()]);
            } elseif ($property->isInitialized($object)) {
                $property->setValue($copy, $property->getValue($object));
            }
        }

        return $copy;
    }
}
