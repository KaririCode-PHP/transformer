<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Core;

use KaririCode\Contract\Inspection\PropertyAttributeHandler;
use KaririCode\Transformer\Attribute\Transform;

// ── Core: AttributeRules ─────────────────────────────────────────────
// Collects #[Transform] attributes from an inspected object.
// ─────────────────────────────────────────────────────────────────────

/**
 * Receives, from a kariricode/contract PropertyInspector, every
 * #[Transform] attribute of an object with the value of its property, and
 * turns them into the data and rule map the engine transforms.
 *
 * Preconditions: one instance per transformed object; attributes of other
 * classes are ignored.
 *
 * Thread-safety: mutable while the inspector fills it; not shared.
 *
 * Side effects: handleAttribute() records what it receives.
 *
 * Example:
 *
 *     $rules = new AttributeRules();
 *     $inspector->inspect(object: $dto, attributeClass: Transform::class, handler: $rules);
 *     $engine->transform($rules->values(), $rules->fieldRules());
 *
 * References: kariricode/contract ADR-007 (Inspection); ADR-003.
 */
final class AttributeRules implements PropertyAttributeHandler
{
    /** @var array<string, mixed> */
    private array $values = [];

    /** @var array<string, list<mixed>> */
    private array $rules = [];

    #[\Override]
    public function handleAttribute(string $propertyName, object $attribute, mixed $value): void
    {
        if ($attribute instanceof Transform) {
            $this->values += [$propertyName => $value];
            $this->rules[$propertyName] = [...$this->rules[$propertyName] ?? [], ...$attribute->rules];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function values(): array
    {
        return $this->values;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function fieldRules(): array
    {
        return $this->rules;
    }
}
