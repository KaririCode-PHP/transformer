<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Tests\Unit\Core;

use Closure;
use KaririCode\Transformer\Core\DataPath;
use DateTimeImmutable;
use DateTimeZone;
use KaririCode\Transformer\Core\DateValue;
use KaririCode\Transformer\Core\IntegerParameter;
use KaririCode\Transformer\Core\NumericValue;
use KaririCode\Transformer\Core\RuleContext;
use KaririCode\Transformer\Exception\InvalidRuleException;
use KaririCode\Transformer\Exception\TransformerCode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// ── Unit: RuleContextTest ────────────────────────────────────────────
// The context: copies, typed parameters, field paths, values.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(RuleContext::class)]
#[CoversClass(DataPath::class)]
#[CoversClass(NumericValue::class)]
#[CoversClass(DateValue::class)]
#[CoversClass(IntegerParameter::class)]
final class RuleContextTest extends TestCase
{
    #[Test]
    public function copiesKeepWhatTheyDoNotChange(): void
    {
        $context = new RuleContext(data: ['a' => 1], field: 'a', parameters: ['min' => 1]);
        $field = $context->withField('b');
        $parameters = $context->withParameters(['max' => 2, 'min' => 0]);

        self::assertSame([['a' => 1], 'b', ['min' => 1]], [$field->data, $field->field, $field->parameters]);
        self::assertSame([['a' => 1], 'a', ['min' => 0, 'max' => 2]], [$parameters->data, $parameters->field, $parameters->parameters]);
        self::assertSame(['min' => 1], $context->parameters);
        self::assertSame(['', [], []], [(new RuleContext())->field, (new RuleContext())->data, (new RuleContext())->parameters]);
    }

    #[Test]
    public function parametersAreReadWithTheirTypesChecked(): void
    {
        $context = new RuleContext(field: 'f', parameters: ['int' => 3, 'float' => 1.5, 'text' => 'x', 'list' => [1], 'null' => null, 'no' => false]);

        self::assertTrue($context->hasParameter('null'));
        self::assertFalse($context->hasParameter('missing'));
        self::assertNull($context->parameter('null', 'default'));
        self::assertSame('default', $context->parameter('missing', 'default'));
        self::assertNull($context->parameter('missing'));
        self::assertSame(3, $context->intParameter('int'));
        self::assertSame(7, $context->intParameter('missing', 7));
        self::assertSame(3, $context->numberParameter('int'));
        self::assertSame(1.5, $context->numberParameter('float'));
        self::assertSame(2.5, $context->numberParameter('missing', 2.5));
        self::assertSame('x', $context->stringParameter('text'));
        self::assertSame('y', $context->stringParameter('missing', 'y'));
        self::assertFalse($context->boolParameter('no', true));
        self::assertTrue($context->boolParameter('missing', true));
        self::assertFalse($context->boolParameter('missing', false));
        self::assertSame([1], $context->arrayParameter('list'));
    }

    /**
     * @return iterable<string, array{Closure(RuleContext): mixed, TransformerCode, non-empty-string}>
     */
    public static function refusals(): iterable
    {
        yield 'int missing' => [static fn(RuleContext $c): mixed => $c->intParameter('missing'), TransformerCode::MissingRuleParameter, 'needs the parameter "missing".'];
        yield 'int from a float' => [static fn(RuleContext $c): mixed => $c->intParameter('float'), TransformerCode::InvalidRuleParameter, 'must be an integer, float given.'];
        yield 'int from null' => [static fn(RuleContext $c): mixed => $c->intParameter('null', 1), TransformerCode::InvalidRuleParameter, 'must be an integer, null given.'];
        yield 'number from text' => [static fn(RuleContext $c): mixed => $c->numberParameter('text'), TransformerCode::InvalidRuleParameter, 'must be a finite number, string given.'];
        yield 'number from NAN' => [static fn(RuleContext $c): mixed => $c->numberParameter('nan'), TransformerCode::InvalidRuleParameter, 'must be a finite number, float given.'];
        yield 'number missing' => [static fn(RuleContext $c): mixed => $c->numberParameter('missing'), TransformerCode::MissingRuleParameter, 'needs the parameter "missing".'];
        yield 'string from an int' => [static fn(RuleContext $c): mixed => $c->stringParameter('int', 'default'), TransformerCode::InvalidRuleParameter, 'must be a string, int given.'];
        yield 'string missing' => [static fn(RuleContext $c): mixed => $c->stringParameter('missing'), TransformerCode::MissingRuleParameter, 'needs the parameter "missing".'];
        yield 'bool from an int' => [static fn(RuleContext $c): mixed => $c->boolParameter('int', false), TransformerCode::InvalidRuleParameter, 'must be a boolean, int given.'];
        yield 'bool from text' => [static fn(RuleContext $c): mixed => $c->boolParameter('text'), TransformerCode::InvalidRuleParameter, 'must be a boolean, string given.'];
        yield 'bool missing' => [static fn(RuleContext $c): mixed => $c->boolParameter('missing'), TransformerCode::MissingRuleParameter, 'needs the parameter "missing".'];
        yield 'array from text' => [static fn(RuleContext $c): mixed => $c->arrayParameter('text'), TransformerCode::InvalidRuleParameter, 'must be an array, string given.'];
        yield 'array missing' => [static fn(RuleContext $c): mixed => $c->arrayParameter('missing'), TransformerCode::MissingRuleParameter, 'needs the parameter "missing".'];
    }

    /**
     * A parameter that is present is checked even when a default exists:
     * the default covers absence only.
     *
     * @param Closure(RuleContext): mixed $read
     * @param non-empty-string            $ending
     */
    #[Test]
    #[DataProvider('refusals')]
    public function wrongParametersAreTheApplicationsMistake(Closure $read, TransformerCode $code, string $ending): void
    {
        $context = new RuleContext(field: 'age', parameters: ['int' => 3, 'float' => 1.5, 'text' => 'x', 'null' => null, 'nan' => NAN]);

        try {
            $read($context);
            self::fail('The parameter was accepted.');
        } catch (InvalidRuleException $exception) {
            self::assertSame($code, $exception->errorCode);
            self::assertStringContainsString('field "age"', $exception->getMessage());
            self::assertStringEndsWith($ending, $exception->getMessage());
        }
    }

    #[Test]
    public function fieldsAreFoundByDottedPathsLiteralKeysFirst(): void
    {
        $context = new RuleContext(data: ['user' => ['name' => 'Ana', 'nick' => null], 'items' => ['x', 'y'], 'a.b' => 'literal', 'a' => ['b' => 'nested']]);

        self::assertSame('Ana', $context->fieldValue('user.name'));
        self::assertSame('y', $context->fieldValue('items.1'));
        self::assertSame('literal', $context->fieldValue('a.b'));
        self::assertTrue($context->hasField('user.nick'));
        self::assertNull($context->fieldValue('user.nick'));
        self::assertFalse($context->hasField('user.age'));
        self::assertNull($context->fieldValue('user.age'));
        self::assertFalse($context->hasField('user.name.first'));
        self::assertFalse($context->hasField('missing'));
        self::assertTrue(DataPath::exists(['' => 1], ''));
    }

    #[Test]
    public function fieldsAreReplacedInACopy(): void
    {
        $data = ['user' => ['name' => ' Ana ', 'age' => 3], 'a.b' => 'literal', 'a' => ['b' => 'nested'], 'items' => ['x', 'y']];

        self::assertSame(['user' => ['name' => 'Ana', 'age' => 3], 'a.b' => 'literal', 'a' => ['b' => 'nested'], 'items' => ['x', 'y']], DataPath::with($data, 'user.name', 'Ana'));
        self::assertSame(['user' => ['name' => ' Ana ', 'age' => 3], 'a.b' => 'LITERAL', 'a' => ['b' => 'nested'], 'items' => ['x', 'y']], DataPath::with($data, 'a.b', 'LITERAL'));
        self::assertSame(['x', 'Y'], DataPath::with($data, 'items.1', 'Y')['items']);
        self::assertSame(['top' => 1], DataPath::with([], 'top', 1));
        self::assertSame(['new' => ['inner' => 1]], DataPath::with([], 'new.inner', 1));
        self::assertSame(['scalar' => ['inner' => 1]], DataPath::with(['scalar' => 'text'], 'scalar.inner', 1));
    }

    /**
     * @return iterable<string, array{mixed, int|float|null}>
     */
    public static function numbers(): iterable
    {
        yield 'int' => [42, 42];
        yield 'negative int string' => ['-7', -7];
        yield 'padded int string' => [' 12 ', 12];
        yield 'float' => [1.5, 1.5];
        yield 'float string' => ['1.5', 1.5];
        yield 'exponent string' => ['1e3', 1000.0];
        yield 'leading zero string' => ['012', 12.0];
        yield 'int beyond PHP_INT_MAX' => ['9223372036854775808', 9.2233720368547758E18];
        yield 'infinity' => [INF, null];
        yield 'NAN' => [NAN, null];
        yield 'true' => [true, null];
        yield 'text' => ['12abc', null];
        yield 'null' => [null, null];
    }

    #[Test]
    #[DataProvider('numbers')]
    public function numericValuesKeepIntegersAsIntegers(mixed $value, int|float|null $expected): void
    {
        self::assertSame($expected, NumericValue::of($value));
    }

    #[Test]
    public function datesAreReadStrictlyInTheirZone(): void
    {
        $utc = new DateTimeZone('UTC');
        $fortaleza = new DateTimeZone('America/Fortaleza');

        self::assertSame('2026-11-28 00:00:00 +00:00', DateValue::parse(value: '28/11/2026', format: 'd/m/Y', zone: $utc)?->format('Y-m-d H:i:s P'));
        self::assertSame('2026-11-28 00:00:00 -03:00', DateValue::parse(value: '28/11/2026', format: 'd/m/Y', zone: $fortaleza)?->format('Y-m-d H:i:s P'));
        self::assertSame('+02:00', DateValue::parse(value: '2026-11-28T10:00:00+02:00', format: 'Y-m-d\TH:i:sP', zone: $fortaleza)?->format('P'));
        self::assertNull(DateValue::parse(value: '31/02/2026', format: 'd/m/Y', zone: $utc));
        self::assertNull(DateValue::parse(value: '28/11/2026 10:00', format: 'd/m/Y', zone: $utc));
        self::assertNull(DateValue::parse(value: '2026-11-28', format: 'd/m/Y', zone: $utc));
        self::assertNull(DateValue::parse(value: '28/11/2026 25:00', format: 'd/m/Y H:i', zone: $utc));
    }

    #[Test]
    public function theZoneAndTheClockComeFromTheParameters(): void
    {
        $now = new DateTimeImmutable('2026-11-28 12:00:00', new DateTimeZone('UTC'));
        $mutable = new \DateTime('2026-01-01 00:00:00', new DateTimeZone('UTC'));

        self::assertSame('UTC', DateValue::zone(new RuleContext())->getName());
        self::assertSame('America/Fortaleza', DateValue::zone(new RuleContext(parameters: ['timezone' => 'America/Fortaleza']))->getName());
        self::assertEquals($now, DateValue::now(new RuleContext(parameters: ['now' => $now]), new DateTimeZone('UTC')));
        self::assertSame('2026-01-01', DateValue::now(new RuleContext(parameters: ['now' => $mutable]), new DateTimeZone('UTC'))->format('Y-m-d'));
        self::assertEqualsWithDelta(time(), DateValue::now(new RuleContext(), new DateTimeZone('UTC'))->getTimestamp(), 5);
        self::assertSame('America/Fortaleza', DateValue::now(new RuleContext(), new DateTimeZone('America/Fortaleza'))->getTimezone()->getName());

        foreach ([
            [static fn(): mixed => DateValue::zone(new RuleContext(field: 'd', parameters: ['timezone' => ''])), 'must be a time zone identifier.'],
            [static fn(): mixed => DateValue::zone(new RuleContext(field: 'd', parameters: ['timezone' => 'Mars/Olympus'])), "must be a time zone identifier, 'Mars/Olympus' given."],
            [static fn(): mixed => DateValue::now(new RuleContext(field: 'd', parameters: ['now' => '2026-11-28']), new DateTimeZone('UTC')), 'must be a DateTimeInterface, string given.'],
            [static fn(): mixed => DateValue::now(new RuleContext(field: 'd', parameters: ['now' => null]), new DateTimeZone('UTC')), 'must be a DateTimeInterface, null given.'],
        ] as [$read, $ending]) {
            try {
                $read();
                self::fail($ending);
            } catch (InvalidRuleException $exception) {
                self::assertSame(TransformerCode::InvalidRuleParameter, $exception->errorCode);
                self::assertStringEndsWith($ending, $exception->getMessage());
            }
        }
    }

    #[Test]
    public function boundedIntegersAreChecked(): void
    {
        self::assertSame(3, IntegerParameter::atLeast(context: new RuleContext(), name: 'times', default: 3, minimum: 0));
        self::assertSame(0, IntegerParameter::atLeast(context: new RuleContext(parameters: ['times' => 0]), name: 'times', default: 3, minimum: 0));
        self::assertSame(512, IntegerParameter::depth(new RuleContext()));
        self::assertSame(1, IntegerParameter::depth(new RuleContext(parameters: ['depth' => 1])));
        self::assertSame(2_147_483_647, IntegerParameter::depth(new RuleContext(parameters: ['depth' => 2_147_483_647])));

        foreach ([
            [static fn(): int => IntegerParameter::atLeast(context: new RuleContext(field: 'f', parameters: ['times' => -1]), name: 'times', default: 3, minimum: 0), 'must be an integer of 0 or more, -1 given.'],
            [static fn(): int => IntegerParameter::atLeast(context: new RuleContext(field: 'f', parameters: ['depth' => 0]), name: 'depth', default: 3, minimum: 1), 'must be an integer of 1 or more, 0 given.'],
            [static fn(): int => IntegerParameter::depth(new RuleContext(field: 'f', parameters: ['depth' => 0])), 'must be an integer from 1 to 2147483647, 0 given.'],
            [static fn(): int => IntegerParameter::depth(new RuleContext(field: 'f', parameters: ['depth' => 2_147_483_648])), 'must be an integer from 1 to 2147483647, 2147483648 given.'],
        ] as [$read, $ending]) {
            try {
                $read();
                self::fail($ending);
            } catch (InvalidRuleException $exception) {
                self::assertSame(TransformerCode::InvalidRuleParameter, $exception->errorCode);
                self::assertStringEndsWith($ending, $exception->getMessage());
            }
        }
    }

    /**
     * @return iterable<string, array{mixed, ?int}>
     */
    public static function integers(): iterable
    {
        yield 'int' => [42, 42];
        yield 'whole float' => [42.0, 42];
        yield 'exponent string' => ['1e3', 1000];
        yield 'leading zero string' => ['012', 12];
        yield 'negative whole float' => [-7.0, -7];
        yield 'smallest int as a float' => [(float) PHP_INT_MIN, PHP_INT_MIN];
        yield 'fraction' => [1.5, null];
        yield 'beyond the ints' => ['9223372036854775808', null];
        yield 'text' => ['x', null];
        yield 'true' => [true, null];
    }

    #[Test]
    #[DataProvider('integers')]
    public function integralNumbersReadAsIntegers(mixed $value, ?int $expected): void
    {
        self::assertSame($expected, NumericValue::integer($value));
    }
}
