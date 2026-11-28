<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Structure;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\DataPath;

// ── Rule: PluckRule ──────────────────────────────────────────────────
// Takes one field out of every record of a list.
// ─────────────────────────────────────────────────────────────────────

/**
 * Returns the list of each record's 'field', a dotted path into nested
 * arrays (a key containing dots is matched first); records that are not
 * arrays or lack the field give null, so positions are kept.
 *
 * Preconditions: 'field' is a string.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['emails' => [['pluck', ['field' => 'contact.email']]]]
 *
 * References: SPEC-001 §4; SPEC-002 §6.
 */
final readonly class PluckRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $field = $context->stringParameter('field');

        if (!is_array($value)) {
            return $value;
        }

        return array_values(array_map(static fn(mixed $record): mixed => is_array($record) ? DataPath::value($record, $field) : null, $value));
    }

    #[\Override]
    public function name(): string
    {
        return 'structure.pluck';
    }
}
