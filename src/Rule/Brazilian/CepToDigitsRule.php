<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Brazilian;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;

// ── Rule: CepToDigitsRule ────────────────────────────────────────────
// Writes a CEP as its 8 digits.
// ─────────────────────────────────────────────────────────────────────

/**
 * Removes the hyphen from a CEP written bare or formatted (63100-000,
 * 63100000); any other string is returned unchanged.
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['cep' => ['cep_to_digits']]
 *
 * References: Correios, CEP; SPEC-002 §7.
 */
final readonly class CepToDigitsRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        return is_string($value) && preg_match('/\A\d{5}-?\d{3}\z/', $value) === 1 ? str_replace(search: '-', replace: '', subject: $value) : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'brazilian.cep_to_digits';
    }
}
