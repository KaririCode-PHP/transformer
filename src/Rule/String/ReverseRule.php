<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\String;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;

// ── Rule: ReverseRule ────────────────────────────────────────────────
// Reverses a string, character cluster by cluster.
// ─────────────────────────────────────────────────────────────────────

/**
 * Reverses the order of the user-perceived characters (Unicode extended
 * grapheme clusters), so accented letters, flags and emoji sequences
 * stay whole: "ação" becomes "oãça". Invalid UTF-8 bytes become "?"
 * first.
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['palindrome_check' => ['reverse']]
 *
 * References: Unicode UAX #29 (grapheme clusters); SPEC-002 §1.
 */
final readonly class ReverseRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        preg_match_all(pattern: '/\X/u', subject: mb_scrub($value, 'UTF-8'), matches: $clusters);

        return implode('', array_reverse($clusters[0]));
    }

    #[\Override]
    public function name(): string
    {
        return 'string.reverse';
    }
}
