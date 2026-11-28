<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Structure;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Exception\InvalidRuleException;

// ── Rule: UnflattenRule ──────────────────────────────────────────────
// Turns joined keys back into nested arrays.
// ─────────────────────────────────────────────────────────────────────

/**
 * Splits each key at 'separator' ("." unless set) and nests the value
 * under the parts: ['a.b' => 1, 'a.c' => 2] becomes
 * ['a' => ['b' => 1, 'c' => 2]]. When a key names a value that another
 * key needs as an array (['a' => 1, 'a.b' => 2]), or two keys name the
 * same place, the array is returned unchanged instead of overwriting
 * one with the other.
 *
 * Preconditions: 'separator' is a non-empty string.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['form' => ['unflatten']]
 *
 * References: ADR-008; SPEC-002 §6.
 */
final readonly class UnflattenRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $separator = $context->stringParameter('separator', '.');

        if ($separator === '') {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'separator', expected: 'a non-empty string');
        }

        if (!is_array($value)) {
            return $value;
        }

        $tree = [];

        /** @var mixed $item */
        foreach ($value as $key => $item) {
            $tree = self::put(tree: $tree, segments: explode($separator, (string) $key), item: $item);

            if ($tree === null) {
                return $value;
            }
        }

        return $tree;
    }

    #[\Override]
    public function name(): string
    {
        return 'structure.unflatten';
    }

    /**
     * @param array<array-key, mixed> $tree
     * @param non-empty-list<string>  $segments
     *
     * @return array<array-key, mixed>|null null when the place is taken
     */
    private static function put(array $tree, array $segments, mixed $item): ?array
    {
        $segment = $segments[0];
        $rest = array_slice($segments, 1);

        if ($rest === []) {
            return array_key_exists($segment, $tree) ? null : $tree + [$segment => $item];
        }

        /** @var mixed $branch */
        $branch = $tree[$segment] ?? [];
        $branch = is_array($branch) ? self::put(tree: $branch, segments: $rest, item: $item) : null;

        return $branch === null ? null : array_replace($tree, [$segment => $branch]);
    }
}
