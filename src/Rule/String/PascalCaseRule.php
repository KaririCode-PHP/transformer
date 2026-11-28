<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\String;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\Words;

// ── Rule: PascalCaseRule ─────────────────────────────────────────────
// Writes a phrase or an identifier in PascalCase.
// ─────────────────────────────────────────────────────────────────────

/**
 * Splits the string into words (at separators and at the case changes
 * of an identifier, Unicode letters included) and joins them
 * capitalized: "user first_name" becomes "UserFirstName", "élan vital"
 * becomes "ÉlanVital".
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['class' => ['pascal_case']]
 *
 * References: ADR-005; SPEC-002 §1.
 */
final readonly class PascalCaseRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        return is_string($value) ? implode('', array_map(Words::capitalize(...), Words::of($value))) : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'string.pascal_case';
    }
}
