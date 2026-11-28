<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Brazilian;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;

// ── Rule: PhoneFormatRule ────────────────────────────────────────────
// Writes a Brazilian phone number as (DD) NNNNN-NNNN.
// ─────────────────────────────────────────────────────────────────────

/**
 * Reads a Brazilian phone number written with digits, spaces, dots,
 * hyphens and parentheses, with or without +55 in front, and writes its
 * 10 digits (landline) as "(88) 3521-0000" or its 11 digits (mobile) as
 * "(88) 99999-0000". Numbers with another count of digits, an area code
 * starting with 0 (a carrier prefix) or other characters are returned
 * unchanged.
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['phone' => ['phone_format']]
 *
 * References: Anatel, Plano de Numeração; SPEC-002 §7.
 */
final readonly class PhoneFormatRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        if (!is_string($value) || preg_match('/\A(?:\+55)?[\d\s().-]+\z/', $value) !== 1) {
            return $value;
        }

        $digits = substr(string: preg_replace(pattern: '/\D/', replacement: '', subject: $value) ?? '', offset: str_starts_with($value, '+55') ? 2 : 0);

        return match (true) {
            str_starts_with($digits, '0') => $value,
            strlen($digits) === 10 => sprintf('(%s) %s-%s', substr(string: $digits, offset: 0, length: 2), substr(string: $digits, offset: 2, length: 4), substr($digits, 6)),
            strlen($digits) === 11 => sprintf('(%s) %s-%s', substr(string: $digits, offset: 0, length: 2), substr(string: $digits, offset: 2, length: 5), substr($digits, 7)),
            default => $value,
        };
    }

    #[\Override]
    public function name(): string
    {
        return 'brazilian.phone_format';
    }
}
