<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Structure;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Exception\InvalidRuleException;

// ── Rule: RenameKeysRule ─────────────────────────────────────────────
// Renames the keys of an array.
// ─────────────────────────────────────────────────────────────────────

/**
 * Renames the top-level keys the 'map' lists (old key => new key) and
 * keeps the others and the order. When a new name meets a key that is
 * already there, the array is returned unchanged instead of one value
 * replacing the other; swapping two names works.
 *
 * Preconditions: 'map' is a non-empty array of integers or strings.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['payload' => [['rename_keys', ['map' => ['e-mail' => 'email']]]]]
 *
 * References: SPEC-002 §6.
 */
final readonly class RenameKeysRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $map = $context->arrayParameter('map');

        if ($map === [] || !array_all($map, static fn(mixed $name): bool => is_int($name) || is_string($name))) {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'map', expected: 'a non-empty map of keys to new names');
        }

        if (!is_array($value)) {
            return $value;
        }

        /** @var array<array-key, int|string> $map */
        $renamed = [];

        /** @var mixed $item */
        foreach ($value as $key => $item) {
            $name = $map[$key] ?? $key;

            if (array_key_exists($name, $renamed)) {
                return $value;
            }

            $renamed += [$name => $item];
        }

        return $renamed;
    }

    #[\Override]
    public function name(): string
    {
        return 'structure.rename_keys';
    }
}
