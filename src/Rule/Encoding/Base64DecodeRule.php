<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Encoding;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;

// ── Rule: Base64DecodeRule ───────────────────────────────────────────
// Decodes a Base64 string.
// ─────────────────────────────────────────────────────────────────────

/**
 * Decodes standard Base64 (whitespace is ignored and padding may be
 * left out). Text outside the alphabet, and encodings that are not
 * canonical — unused bits set, so two texts would decode to the same
 * bytes — are returned unchanged. The result is bytes, not necessarily
 * UTF-8.
 *
 * Preconditions: none; other types are returned unchanged.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['attachment' => ['base64_decode']]
 *
 * References: RFC 4648 §3.5, §4; SPEC-002 §3.
 */
final readonly class Base64DecodeRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return $value;
        }

        $written = rtrim(preg_replace(pattern: '/\s+/', replacement: '', subject: $value) ?? '', '=');

        return rtrim(base64_encode($decoded), '=') === $written ? $decoded : $value;
    }

    #[\Override]
    public function name(): string
    {
        return 'encoding.base64_decode';
    }
}
