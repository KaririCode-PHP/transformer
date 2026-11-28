<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\String;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\IntegerParameter;
use KaririCode\Transformer\Exception\InvalidRuleException;

// ── Rule: MaskRule ───────────────────────────────────────────────────
// Hides the middle of a string behind a mask character.
// ─────────────────────────────────────────────────────────────────────

/**
 * Keeps the first 'keep_start' and the last 'keep_end' characters (3
 * and 3 unless set) and replaces each character between them with
 * 'char' ("*" unless set), so the length stays visible and the content
 * does not. A string too short to keep both ends is masked entirely:
 * a short secret is never shown whole.
 *
 * Preconditions: 'keep_start' and 'keep_end' are integers of 0 or more; 'char' is one character.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['card' => [['mask', ['keep_start' => 0, 'keep_end' => 4]]]]; // '4111111111111111' → '************1111'
 *
 * References: OWASP ASVS 5.0 V14 (data protection); SPEC-002 §1.
 */
final readonly class MaskRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $keepStart = IntegerParameter::atLeast(context: $context, name: 'keep_start', default: 3, minimum: 0);
        $keepEnd = IntegerParameter::atLeast(context: $context, name: 'keep_end', default: 3, minimum: 0);
        $char = $context->stringParameter('char', '*');

        if (mb_strlen($char, 'UTF-8') !== 1) {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'char', expected: 'one character', given: var_export($char, true));
        }

        if (!is_string($value)) {
            return $value;
        }

        $length = mb_strlen($value, 'UTF-8');

        if ($keepStart + $keepEnd >= $length) {
            return str_repeat($char, $length);
        }

        return mb_substr(string: $value, start: 0, length: $keepStart, encoding: 'UTF-8')
            . str_repeat($char, $length - $keepStart - $keepEnd)
            . mb_substr(string: $value, start: $length - $keepEnd, encoding: 'UTF-8');
    }

    #[\Override]
    public function name(): string
    {
        return 'string.mask';
    }
}
