<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Data;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;

// ── Rule: ArrayToKeyValueRule ────────────────────────────────────────
// Turns a list of records into a map of one field to another.
// ─────────────────────────────────────────────────────────────────────

/**
 * Maps each record's 'key' field ("id" unless set) to its 'value' field
 * ("name" unless set), like array_column(): a later record with the
 * same key replaces an earlier one. The list is returned unchanged when
 * a record is not an array, lacks either field, or has a key that is
 * not an integer or a string, rather than dropping records silently.
 *
 * Preconditions: 'key' and 'value', when given, are strings.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['countries' => [['array_to_key_value', ['key' => 'code', 'value' => 'name']]]]
 *
 * References: PHP array_column(); ADR-008; SPEC-002 §2.
 */
final readonly class ArrayToKeyValueRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $keyField = $context->stringParameter('key', 'id');
        $valueField = $context->stringParameter('value', 'name');

        if (!is_array($value)) {
            return $value;
        }

        /** @var mixed $record */
        foreach ($value as $record) {
            if (!is_array($record) || !array_key_exists($valueField, $record) || !self::isKey($record[$keyField] ?? null)) {
                return $value;
            }
        }

        return array_column(array: $value, column_key: $valueField, index_key: $keyField);
    }

    #[\Override]
    public function name(): string
    {
        return 'data.array_to_key_value';
    }

    private static function isKey(mixed $key): bool
    {
        return is_int($key) || is_string($key);
    }
}
