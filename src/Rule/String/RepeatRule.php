<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\String;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\IntegerParameter;

// ── Rule: RepeatRule ─────────────────────────────────────────────────
// Repeats a string, with a separator between copies.
// ─────────────────────────────────────────────────────────────────────

/**
 * Joins 'times' copies of the string (2 unless set; 0 gives the empty
 * string) with 'separator' (the empty string unless set).
 *
 * Preconditions: 'times' is an integer of 0 or more; 'separator', when given, is a string.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['divider' => [['repeat', ['times' => 3, 'separator' => ' ']]]]
 *
 * References: SPEC-002 §1.
 */
final readonly class RepeatRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $times = IntegerParameter::atLeast(context: $context, name: 'times', default: 2, minimum: 0);
        $separator = $context->stringParameter('separator', '');

        if (!is_string($value)) {
            return $value;
        }

        return $times === 0 ? '' : $value . str_repeat($separator . $value, $times - 1);
    }

    #[\Override]
    public function name(): string
    {
        return 'string.repeat';
    }
}
