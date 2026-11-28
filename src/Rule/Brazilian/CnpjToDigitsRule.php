<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Brazilian;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;

// ── Rule: CnpjToDigitsRule ───────────────────────────────────────────
// Writes a CNPJ as its 14 characters.
// ─────────────────────────────────────────────────────────────────────

/**
 * Removes the dots, the slash and the hyphen from a CNPJ written bare or
 * formatted, numeric or alphanumeric (IN RFB nº 2.229/2024: twelve
 * characters 0–9 or A–Z, upper case, then two digits; each separator
 * optional), keeping the letters: 12.ABC.345/01DE-35 becomes
 * 12ABC34501DE35. Any other string is returned unchanged. It does not
 * check the digits: validate with kariricode/validator's cnpj rule.
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['cnpj' => ['cnpj_to_digits']]
 *
 * References: IN RFB nº 2.229/2024 (alphanumeric CNPJ); ADR-008; SPEC-002 §7.
 */
final readonly class CnpjToDigitsRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        return is_string($value) && preg_match('/\A[0-9A-Z]{2}\.?[0-9A-Z]{3}\.?[0-9A-Z]{3}\/?[0-9A-Z]{4}-?\d{2}\z/', $value) === 1
            ? str_replace(search: ['.', '/', '-'], replace: '', subject: $value)
            : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'brazilian.cnpj_to_digits';
    }
}
