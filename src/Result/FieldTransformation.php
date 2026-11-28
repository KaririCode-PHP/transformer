<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Result;

// ── Result: FieldTransformation ──────────────────────────────────────
// One change a rule made to one field.
// ─────────────────────────────────────────────────────────────────────

/**
 * Records that a rule changed the value of a field: the field as written
 * in the rule map, the rule's name, and the value before and after.
 * Rules that leave a value as it was record nothing.
 *
 * Preconditions: built by the engine when transformation tracking is on.
 *
 * Thread-safety: immutable.
 *
 * Side effects: none; before and after hold input values, so do not log
 * them where the input itself may not go.
 *
 * Example:
 *
 *     $change->field;    // 'column'
 *     $change->ruleName; // 'string.snake_case'
 *     $change->before;   // 'firstName'
 *
 * References: ADR-002; SPEC-001 §6.
 */
final readonly class FieldTransformation
{
    public function __construct(
        public string $field,
        public string $ruleName,
        public mixed $before,
        public mixed $after,
    ) {}
}
