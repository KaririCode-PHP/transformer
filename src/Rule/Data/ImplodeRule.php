<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Data;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use Stringable;

// ── Rule: ImplodeRule ────────────────────────────────────────────────
// Joins a list of scalars into a string.
// ─────────────────────────────────────────────────────────────────────

/**
 * Joins the items with 'separator' ("," unless set) when every item is
 * an integer, a float, a string or a Stringable object; keys are
 * ignored. A list holding anything else (arrays, null, booleans, other
 * objects) is returned unchanged instead of failing or writing "Array".
 *
 * Preconditions: 'separator', when given, is a string.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['tags' => [['implode', ['separator' => ', ']]]]
 *
 * References: SPEC-002 §2.
 */
final readonly class ImplodeRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $separator = $context->stringParameter('separator', ',');

        if (!is_array($value)) {
            return $value;
        }

        $items = [];

        /** @var mixed $item */
        foreach ($value as $item) {
            if (!is_int($item) && !is_float($item) && !is_string($item) && !$item instanceof Stringable) {
                return $value;
            }

            $items[] = $item;
        }

        return implode($separator, $items);
    }

    #[\Override]
    public function name(): string
    {
        return 'data.implode';
    }
}
