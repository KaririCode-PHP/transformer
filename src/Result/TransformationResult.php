<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Result;

use KaririCode\Transformer\Core\DataPath;

// ── Result: TransformationResult ─────────────────────────────────────
// The input, its transformed copy and every change between them.
// ─────────────────────────────────────────────────────────────────────

/**
 * Holds the input as it came, the transformed copy with the same shape, and
 * the changes the rules made in order. Fields read by dotted path.
 * transformedFields(), transformationsFor() and transformationCount() read the
 * record of changes, which is empty when tracking is off; wasTransformed()
 * and isFieldTransformed() compare the data itself.
 *
 * Preconditions: none.
 *
 * Thread-safety: immutable.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     $result = $engine->transform(['name' => 'firstName'], ['name' => ['snake_case', 'kebab_case']]);
 *     $result->get('name');                // 'first-name'
 *     $result->transformationsFor('name'); // snake_case, then kebab_case
 *
 * References: ADR-002; SPEC-001 §6.
 */
final readonly class TransformationResult
{
    /**
     * @param array<array-key, mixed> $original
     * @param array<array-key, mixed> $transformed
     * @param list<FieldTransformation> $transformations
     */
    public function __construct(
        public array $original,
        public array $transformed,
        public array $transformations = [],
    ) {}

    public function get(string $field): mixed
    {
        return DataPath::value($this->transformed, $field);
    }

    public function wasTransformed(): bool
    {
        return $this->original !== $this->transformed;
    }

    public function isFieldTransformed(string $field): bool
    {
        return DataPath::value($this->original, $field) !== DataPath::value($this->transformed, $field);
    }

    /**
     * @return list<string> the fields changes were recorded for, in the order they changed
     */
    public function transformedFields(): array
    {
        return array_values(array_unique(array_map(static fn(FieldTransformation $change): string => $change->field, $this->transformations)));
    }

    /**
     * @return list<FieldTransformation>
     */
    public function transformationsFor(string $field): array
    {
        return array_values(array_filter($this->transformations, static fn(FieldTransformation $change): bool => $change->field === $field));
    }

    public function transformationCount(): int
    {
        return count($this->transformations);
    }
}
