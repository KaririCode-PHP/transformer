<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Data;

use JsonException;
use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\IntegerParameter;
use KaririCode\Transformer\Exception\InvalidRuleException;

// ── Rule: JsonEncodeRule ─────────────────────────────────────────────
// Encodes a value as JSON.
// ─────────────────────────────────────────────────────────────────────

/**
 * Encodes any value json_encode() accepts with the 'flags' given
 * (JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES |
 * JSON_PRESERVE_ZERO_FRACTION unless set, so 1.0 stays 1.0) and the
 * 'depth' (512 unless set). A value JSON cannot hold (invalid UTF-8,
 * INF, a resource, nesting deeper than 'depth') is returned unchanged.
 * JSON_PARTIAL_OUTPUT_ON_ERROR is refused: it writes null for what it
 * cannot encode.
 *
 * Preconditions: 'flags' is an integer without JSON_PARTIAL_OUTPUT_ON_ERROR; 'depth' is an integer from 1 to 2147483647.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     ['payload' => ['json_encode']]
 *
 * References: RFC 8259; ADR-008; SPEC-002 §2.
 */
final readonly class JsonEncodeRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        $flags = $context->intParameter('flags', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
        $depth = IntegerParameter::depth($context);

        if (($flags & JSON_PARTIAL_OUTPUT_ON_ERROR) !== 0) {
            throw InvalidRuleException::invalidParameter(field: $context->field, parameter: 'flags', expected: 'flags without JSON_PARTIAL_OUTPUT_ON_ERROR, which writes null for what it cannot encode');
        }

        try {
            return json_encode(value: $value, flags: $flags | JSON_THROW_ON_ERROR, depth: $depth);
        } catch (JsonException) {
            return $value;
        }
    }

    #[\Override]
    public function name(): string
    {
        return 'data.json_encode';
    }
}
