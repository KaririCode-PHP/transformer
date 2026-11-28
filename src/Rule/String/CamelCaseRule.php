<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\String;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\Words;

// ── Rule: CamelCaseRule ──────────────────────────────────────────────
// Writes a phrase or an identifier in camelCase.
// ─────────────────────────────────────────────────────────────────────

/**
 * Splits the string into words (at separators and at the case changes
 * of an identifier, Unicode letters included) and joins them with the
 * first word in lower case and every other word capitalized:
 * "user_first name", "UserFirstName" and "user-first-name" all become
 * "userFirstName"; "HTTPServer" becomes "httpServer".
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['field' => ['camel_case']]
 *
 * References: ADR-005; SPEC-002 §1.
 */
final readonly class CamelCaseRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $words = Words::of($value);

        return mb_strtolower(implode('', array_slice(array: $words, offset: 0, length: 1)), 'UTF-8') . implode('', array_map(Words::capitalize(...), array_slice($words, 1)));
    }

    #[\Override]
    public function name(): string
    {
        return 'string.camel_case';
    }
}
