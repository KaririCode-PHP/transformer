<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\String;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\Words;

// ── Rule: KebabCaseRule ──────────────────────────────────────────────
// Writes a phrase or an identifier in kebab-case.
// ─────────────────────────────────────────────────────────────────────

/**
 * Splits the string into words (at separators and at the case changes
 * of an identifier, Unicode letters included) and joins them in lower
 * case with "-": "userFirstName" becomes "user-first-name", "São Paulo"
 * becomes "são-paulo".
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['slug' => ['kebab_case']]
 *
 * References: ADR-005; SPEC-002 §1.
 */
final readonly class KebabCaseRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        return is_string($value) ? mb_strtolower(implode('-', Words::of($value)), 'UTF-8') : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'string.kebab_case';
    }
}
