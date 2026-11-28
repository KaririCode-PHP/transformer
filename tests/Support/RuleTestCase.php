<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Tests\Support;

use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Core\RuleContext;
use KaririCode\Transformer\Exception\InvalidRuleException;
use KaririCode\Transformer\Exception\TransformerCode;
use PHPUnit\Framework\TestCase;

// ── Support: RuleTestCase ────────────────────────────────────────────
// Shared checks of the rule suites: what comes out, and what is refused.
// ─────────────────────────────────────────────────────────────────────

/** Base of the rule suites: a context for field "field" and two assertions. */
abstract class RuleTestCase extends TestCase
{
    /**
     * @param array<string, mixed> $parameters
     */
    protected static function context(array $parameters = []): RuleContext
    {
        return new RuleContext(data: ['field' => 'x'], field: 'field', parameters: $parameters);
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected static function assertTransforms(mixed $expected, TransformationRule $rule, mixed $value, array $parameters = []): void
    {
        self::assertSame($expected, $rule->transform($value, self::context($parameters)), get_debug_type($value) . ' ' . var_export($value, true));
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected static function assertRefused(TransformerCode $code, TransformationRule $rule, array $parameters, mixed $value = 'value'): void
    {
        try {
            $rule->transform($value, self::context($parameters));
        } catch (InvalidRuleException $exception) {
            self::assertSame($code, $exception->errorCode);
            self::assertStringContainsString('"field"', $exception->getMessage());

            return;
        }

        self::fail('The rule accepted a misconfiguration.');
    }
}
