<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Brazilian;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;

// ── Rule: CpfToDigitsRule ────────────────────────────────────────────
// Writes a CPF as its 11 digits.
// ─────────────────────────────────────────────────────────────────────

/**
 * Removes the dots and the hyphen from a CPF written bare or formatted
 * (each separator optional: 123.456.789-09, 12345678909); any other
 * string is returned unchanged. It does not check the digits: validate
 * with kariricode/validator's cpf rule.
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['cpf' => ['cpf_to_digits']]
 *
 * References: Receita Federal do Brasil, CPF; SPEC-002 §7.
 */
final readonly class CpfToDigitsRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        return is_string($value) && preg_match('/\A\d{3}\.?\d{3}\.?\d{3}-?\d{2}\z/', $value) === 1
            ? str_replace(search: ['.', '-'], replace: '', subject: $value)
            : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'brazilian.cpf_to_digits';
    }
}
