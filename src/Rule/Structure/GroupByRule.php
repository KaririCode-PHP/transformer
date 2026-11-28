<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Structure;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\DataPath;

// ── Rule: GroupByRule ────────────────────────────────────────────────
// Groups the records of a list by one of their fields.
// ─────────────────────────────────────────────────────────────────────

/**
 * Groups the records under the value of their 'field', a dotted path
 * into nested arrays, keeping their order inside each group. The list
 * is returned unchanged when a record is not an array or its field is
 * missing or not an integer or a string, rather than dropping records
 * or casting the values.
 *
 * Preconditions: 'field' is a string.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['orders' => [['group_by', ['field' => 'status']]]]
 *
 * References: SPEC-001 §4; SPEC-002 §6.
 */
final readonly class GroupByRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $field = $context->stringParameter('field');

        if (!is_array($value)) {
            return $value;
        }

        $groups = [];

        /** @var mixed $record */
        foreach ($value as $record) {
            if (!is_array($record)) {
                return $value;
            }

            /** @var mixed $key */
            $key = DataPath::value($record, $field);

            if (!is_int($key) && !is_string($key)) {
                return $value;
            }

            $groups[$key][] = $record;
        }

        return $groups;
    }

    #[\Override]
    public function name(): string
    {
        return 'structure.group_by';
    }
}
