<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Structure;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Exception\InvalidRuleException;

// ── Rule: FlattenRule ────────────────────────────────────────────────
// Turns nested arrays into one level with joined keys.
// ─────────────────────────────────────────────────────────────────────

/**
 * Replaces nested arrays by their leaves, keyed by the path joined with
 * 'separator' ("." unless set): ['a' => ['b' => 1]] becomes
 * ['a.b' => 1]. Empty arrays are leaves and stay. When two paths
 * would end in the same key (a key "a.b" next to ['a' => ['b' => …]]),
 * the array is returned unchanged instead of losing one of them.
 *
 * Preconditions: 'separator' is a non-empty string.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['settings' => ['flatten']]
 *
 * References: ADR-008; SPEC-002 §6.
 */
final readonly class FlattenRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $separator = $context->stringParameter('separator', '.');

        if ($separator === '') {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'separator', expected: 'a non-empty string');
        }

        return is_array($value) ? self::leaves(array: $value, prefix: null, separator: $separator) ?? $value : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'structure.flatten';
    }

    /**
     * @param array<array-key, mixed> $array
     *
     * @return array<array-key, mixed>|null null when two paths collide
     */
    private static function leaves(array $array, ?string $prefix, string $separator): ?array
    {
        $leaves = [];

        /** @var mixed $item */
        foreach ($array as $key => $item) {
            $path = $prefix === null ? (string) $key : $prefix . $separator . $key;
            $part = is_array($item) && $item !== [] ? self::leaves(array: $item, prefix: $path, separator: $separator) : [$path => $item];

            if ($part === null || array_intersect_key($leaves, $part) !== []) {
                return null;
            }

            $leaves += $part;
        }

        return $leaves;
    }
}
