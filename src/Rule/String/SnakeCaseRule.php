<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\String;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\Words;

// ── Rule: SnakeCaseRule ──────────────────────────────────────────────
// Writes a phrase or an identifier in snake_case.
// ─────────────────────────────────────────────────────────────────────

/**
 * Splits the string into words (at separators and at the case changes
 * of an identifier, Unicode letters included) and joins them in lower
 * case with "_": "userFirstName" becomes "user_first_name",
 * "HTTPServer" becomes "http_server".
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['column' => ['snake_case']]
 *
 * References: ADR-005; SPEC-002 §1.
 */
final readonly class SnakeCaseRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        return is_string($value) ? mb_strtolower(implode('_', Words::of($value)), 'UTF-8') : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'string.snake_case';
    }
}
