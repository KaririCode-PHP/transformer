<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Encoding;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;

// ── Rule: Base64EncodeRule ───────────────────────────────────────────
// Encodes a string as Base64.
// ─────────────────────────────────────────────────────────────────────

/**
 * Encodes the bytes of the string with the standard Base64 alphabet and
 * padding.
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['attachment' => ['base64_encode']]
 *
 * References: RFC 4648 §4; SPEC-002 §3.
 */
final readonly class Base64EncodeRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        return is_string($value) ? base64_encode($value) : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'encoding.base64_encode';
    }
}
