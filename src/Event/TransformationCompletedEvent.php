<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Event;

use KaririCode\Transformer\Result\TransformationResult;

// ── Event: TransformationCompletedEvent ──────────────────────────────
// Reports a finished transformation and how long it took.
// ─────────────────────────────────────────────────────────────────────

/**
 * Gives the observer the result of a transformation and its duration in
 * milliseconds, measured with the monotonic clock. The result holds the
 * input and its transformed copy: log names and counts, not the result
 * itself, where the input may not go.
 *
 * Preconditions: none.
 *
 * Thread-safety: immutable.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     $observer = static function (object $event) use ($metrics): void {
 *         if ($event instanceof TransformationCompletedEvent) {
 *             $metrics->observe('transformation_ms', $event->durationMs);
 *         }
 *     };
 *
 * References: ADR-006; ARFA 1.3 P5.
 */
final readonly class TransformationCompletedEvent
{
    public function __construct(
        public TransformationResult $result,
        public float $durationMs,
    ) {}
}
