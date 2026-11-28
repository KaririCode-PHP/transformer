<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Tests\Unit\Exception;

use Closure;
use KaririCode\Contract\Exception\Configuration;
use KaririCode\Contract\Exception\Validation;
use KaririCode\Transformer\Exception\ConfigurationException;
use KaririCode\Transformer\Exception\InvalidRuleException;
use KaririCode\Transformer\Exception\TransformerCode;
use KaririCode\Transformer\Exception\TransformerException;
use KaririCode\Transformer\Exception\UnsupportedInputException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Throwable;

// ── Unit: ExceptionsTest ─────────────────────────────────────────────
// Each named constructor: its code, its family, its message.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(TransformerCode::class)]
#[CoversClass(InvalidRuleException::class)]
#[CoversClass(ConfigurationException::class)]
#[CoversClass(UnsupportedInputException::class)]
final class ExceptionsTest extends TestCase
{
    /**
     * @return iterable<string, array{Closure(): Throwable, TransformerCode, class-string, string}>
     */
    public static function exceptions(): iterable
    {
        yield 'duplicate alias' => [static fn(): Throwable => InvalidRuleException::duplicateAlias('snake_case'), TransformerCode::DuplicateRuleAlias, Configuration::class, 'A rule is already registered under the alias "snake_case".'];
        yield 'unknown alias' => [static fn(): Throwable => InvalidRuleException::unknownAlias("sna\nke"), TransformerCode::UnknownRuleAlias, Configuration::class, 'No rule is registered under the alias "sna\\nke".'];
        yield 'invalid definition' => [static fn(): Throwable => InvalidRuleException::invalidDefinition('name', 'a reason'), TransformerCode::InvalidRuleDefinition, Configuration::class, 'Invalid rule definition for field "name": a reason.'];
        yield 'empty alias' => [static fn(): Throwable => InvalidRuleException::emptyAlias(), TransformerCode::InvalidRuleDefinition, Configuration::class, 'A rule alias must not be empty.'];
        yield 'missing parameter' => [static fn(): Throwable => InvalidRuleException::missingParameter('items', 'field'), TransformerCode::MissingRuleParameter, Configuration::class, 'A rule on field "items" needs the parameter "field".'];
        yield 'invalid parameter' => [static fn(): Throwable => InvalidRuleException::invalidParameter(field: 'price', parameter: 'decimals', expected: 'an integer', given: 'string'), TransformerCode::InvalidRuleParameter, Configuration::class, 'The parameter "decimals" of a rule on field "price" must be an integer, string given.'];
        yield 'invalid parameter, nothing given' => [static fn(): Throwable => InvalidRuleException::invalidParameter(field: 'card', parameter: 'char', expected: 'one character'), TransformerCode::InvalidRuleParameter, Configuration::class, 'The parameter "char" of a rule on field "card" must be one character.'];
        yield 'named arguments' => [static fn(): Throwable => InvalidRuleException::namedArguments('Transform', ['times']), TransformerCode::NamedAttributeArguments, Configuration::class, "#[Transform] takes no named arguments (times); pass parameters as ['alias', ['parameter' => value]]."];
        yield 'invalid binding' => [static fn(): Throwable => ConfigurationException::invalidBinding('App\\Service', 'string'), TransformerCode::InvalidBinding, Configuration::class, 'The container resolves "App\\\\Service" to string, which is not an instance of it.'];
        yield 'unsupported input' => [static fn(): Throwable => UnsupportedInputException::notAnArray('string'), TransformerCode::UnsupportedInput, Validation::class, 'The transformation processor expects an array of fields, string given.'];
    }

    /**
     * @param Closure(): Throwable $build
     * @param class-string         $family
     */
    #[Test]
    #[DataProvider('exceptions')]
    public function eachExceptionCarriesItsCodeFamilyAndMessage(Closure $build, TransformerCode $code, string $family, string $message): void
    {
        $exception = $build();
        self::assertInstanceOf(TransformerException::class, $exception);
        self::assertInstanceOf($family, $exception);
        self::assertSame($code, $exception->errorCode);
        self::assertSame($code->value, $exception->getCode());
        self::assertSame("[TRF_{$code->value}] {$message}", $exception->getMessage());
    }

    #[Test]
    public function theCodeTableNamesItsPrefixAndBlock(): void
    {
        self::assertSame('TRF', TransformerCode::prefix());
        self::assertSame([3400, 3499], [TransformerCode::block()->first, TransformerCode::block()->last]);
    }
}
