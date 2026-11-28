<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Exception;

use KaririCode\Exception\CodeBlock;
use KaririCode\Exception\ErrorCode;

// ── Exception: TransformerCode ───────────────────────────────────────
// Every code this package throws, inside block TRF 3400–3499.
// ─────────────────────────────────────────────────────────────────────

/**
 * Names each failure the package can throw, inside the block
 * kariricode/exception's CODE-BLOCKS.md records for kariricode/transformer:
 * 3400–3419 rule definitions and the registry, 3420–3429 container
 * bindings, 3440–3449 the integrations.
 *
 * ARFA 1.3 E-003: backed because getCode() reads the integer through
 * kariricode/exception's ErrorCode; every other enum of the package is
 * pure.
 *
 * Preconditions: none; the cases are constants.
 *
 * Thread-safety: immutable.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     $error->errorCode === TransformerCode::UnknownRuleAlias; // [TRF_3402]
 *
 * References: ADR-004; kariricode/exception CODE-BLOCKS.md.
 */
enum TransformerCode: int implements ErrorCode
{
    case DuplicateRuleAlias = 3401;
    case UnknownRuleAlias = 3402;
    case InvalidRuleDefinition = 3403;
    case MissingRuleParameter = 3404;
    case InvalidRuleParameter = 3405;
    case NamedAttributeArguments = 3406;
    case InvalidBinding = 3420;
    case UnsupportedInput = 3440;

    #[\Override]
    public static function prefix(): string
    {
        return 'TRF';
    }

    #[\Override]
    public static function block(): CodeBlock
    {
        return new CodeBlock(3400, 3499);
    }
}
