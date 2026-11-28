<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Core;

use KaririCode\Contract\Inspection\PropertyChangeApplier;
use KaririCode\Contract\Inspection\PropertyInspector;
use KaririCode\Transformer\Attribute\Transform;
use KaririCode\Transformer\Result\TransformedObject;

// ── Core: AttributeTransformer ───────────────────────────────────────
// Transforms an object by the #[Transform] attributes on its properties.
// ─────────────────────────────────────────────────────────────────────

/**
 * Reads an object's #[Transform] attributes through a kariricode/contract
 * PropertyInspector, transforms the property values with the engine, and
 * writes the changed values to a copy through a PropertyChangeApplier,
 * so readonly DTOs work (kariricode/property-inspector 3 implements
 * both). The object passed in is never modified.
 *
 * Preconditions: the inspector decides what an unreadable property means;
 * kariricode/property-inspector's ReflectionPropertyInspector throws for
 * an uninitialized one.
 *
 * Thread-safety: immutable; one AttributeRules per call.
 *
 * Side effects: none beyond the inspector's reads, the applier's copy and
 * the engine's observer.
 *
 * Example:
 *
 *     $transformer = new AttributeTransformer(new TransformerEngine(), new ReflectionPropertyInspector(), new ReflectionChangeApplier());
 *     $converted = $transformer->transform($signUp)->object;
 *
 * References: kariricode/property-inspector ADR-006, ADR-008 and ADR-009; ADR-003.
 */
final readonly class AttributeTransformer
{
    public function __construct(
        private TransformerEngine $engine,
        private PropertyInspector $inspector,
        private PropertyChangeApplier $applier,
    ) {}

    public function transform(object $object): TransformedObject
    {
        $rules = new AttributeRules();
        $this->inspector->inspect(object: $object, attributeClass: Transform::class, handler: $rules);
        $values = $rules->values();
        $result = $this->engine->transform($values, $rules->fieldRules());
        $changes = [];

        /** @var mixed $original */
        foreach ($values as $property => $original) {
            /** @var mixed $value */
            $value = $result->get($property);

            if ($value !== $original) {
                $changes += [$property => $value];
            }
        }

        return new TransformedObject(object: $changes === [] ? $object : $this->applier->applyChanges($object, $changes), result: $result);
    }
}
