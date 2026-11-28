<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Contract;

// ── Contract: TransformationRule ─────────────────────────────────────
// One conversion of a value into another form.
// ─────────────────────────────────────────────────────────────────────

/**
 * Returns the value in another form (another case, format, encoding or
 * structure) and names itself for the record of changes. A rule returns
 * values it does not handle, or cannot convert without loss, unchanged
 * instead of throwing, so rules chain safely; it never fails because of
 * the input. Rules hold no state between calls: whatever varies per use
 * comes in the context's parameters.
 *
 * Preconditions: transform() receives any value, including null.
 *
 * Thread-safety: implementations must be stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     final readonly class CollapseDashesRule implements TransformationRule
 *     {
 *         public function transform(mixed $value, TransformationContext $context): mixed
 *         {
 *             return is_string($value) ? preg_replace(pattern: '/-{2,}/', replacement: '-', subject: $value) ?? '' : $value;
 *         }
 *
 *         public function name(): string
 *         {
 *             return 'custom.collapse_dashes';
 *         }
 *     }
 *
 * References: ADR-001; SPEC-001 §3.
 */
interface TransformationRule
{
    public function transform(mixed $value, TransformationContext $context): mixed;

    public function name(): string;
}
