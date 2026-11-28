<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Exception;

use KaririCode\Exception\ErrorCode;
use Throwable;

// ── Exception: TransformerException ──────────────────────────────────
// Marks every exception kariricode/transformer throws.
// ─────────────────────────────────────────────────────────────────────

/**
 * Lets a caller catch whatever this package throws with one type, while
 * each concrete exception also belongs to one kariricode/contract family
 * (Configuration or Validation) through its kariricode/exception base.
 *
 * Preconditions: none.
 *
 * Thread-safety: implementations are exceptions, created per failure.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     try {
 *         $engine->transform($data, $rules);
 *     } catch (TransformerException $e) {
 *         $code = $e->errorCode; // a TransformerCode case
 *     }
 *
 * References: ADR-004.
 */
interface TransformerException extends Throwable
{
    public ErrorCode $errorCode { get; }
}
