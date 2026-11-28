<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Tests\Unit\Rule;

use DateTimeImmutable;
use DateTimeZone;
use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Exception\InvalidRuleException;
use KaririCode\Transformer\Exception\TransformerCode;
use KaririCode\Transformer\Rule\Date\AgeRule;
use KaririCode\Transformer\Rule\Date\DateToIso8601Rule;
use KaririCode\Transformer\Rule\Date\DateToTimestampRule;
use KaririCode\Transformer\Rule\Date\RelativeDateRule;
use KaririCode\Transformer\Rule\Numeric\CurrencyFormatRule;
use KaririCode\Transformer\Rule\Numeric\NumberToWordsRule;
use KaririCode\Transformer\Rule\Numeric\OrdinalRule;
use KaririCode\Transformer\Rule\Numeric\PercentageRule;
use KaririCode\Transformer\Tests\Support\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

// ── Unit: ValueRulesTest ─────────────────────────────────────────────
// Date and numeric rules: strict dates, a clock that can be given, exact text.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(DateToTimestampRule::class)]
#[CoversClass(DateToIso8601Rule::class)]
#[CoversClass(AgeRule::class)]
#[CoversClass(RelativeDateRule::class)]
#[CoversClass(CurrencyFormatRule::class)]
#[CoversClass(PercentageRule::class)]
#[CoversClass(OrdinalRule::class)]
#[CoversClass(NumberToWordsRule::class)]
final class ValueRulesTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{mixed, TransformationRule, mixed, 3?: array<string, mixed>}>
     */
    public static function cases(): iterable
    {
        $now = new DateTimeImmutable('2026-11-28 12:00:00', new DateTimeZone('UTC'));

        yield 'date_to_timestamp: date in UTC' => [1_795_824_000, new DateToTimestampRule(), '2026-11-28'];
        yield 'date_to_timestamp: format and zone' => [1_795_870_800, new DateToTimestampRule(), '28/11/2026 10:00', ['format' => 'd/m/Y H:i', 'timezone' => 'America/Fortaleza']];
        yield 'date_to_timestamp: the text names its zone' => [1_795_849_200, new DateToTimestampRule(), '2026-11-28T10:00:00+03:00', ['format' => 'Y-m-d\TH:i:sP', 'timezone' => 'America/Fortaleza']];
        yield 'date_to_timestamp: impossible date kept' => ['2026-02-30', new DateToTimestampRule(), '2026-02-30'];
        yield 'date_to_timestamp: trailing data kept' => ['2026-11-28 ', new DateToTimestampRule(), '2026-11-28 '];
        yield 'date_to_timestamp: not a string' => [20261128, new DateToTimestampRule(), 20261128];
        yield 'date_to_iso8601: d/m/Y in UTC' => ['2026-11-28T00:00:00+00:00', new DateToIso8601Rule(), '28/11/2026'];
        yield 'date_to_iso8601: in a zone' => ['2026-11-28T00:00:00-03:00', new DateToIso8601Rule(), '28/11/2026', ['timezone' => 'America/Sao_Paulo']];
        yield 'date_to_iso8601: the text keeps its offset' => ['2026-11-28T10:00:00-03:00', new DateToIso8601Rule(), '2026-11-28 10:00:00-03:00', ['from' => 'Y-m-d H:i:sP']];
        yield 'date_to_iso8601: impossible date kept' => ['31/02/2026', new DateToIso8601Rule(), '31/02/2026'];
        yield 'date_to_iso8601: not a string' => [null, new DateToIso8601Rule(), null];
        yield 'age: before the birthday' => [35, new AgeRule(), '1990-12-01', ['now' => $now]];
        yield 'age: after the birthday' => [36, new AgeRule(), '1990-03-07', ['now' => $now]];
        yield 'age: on the birthday' => [26, new AgeRule(), '2000-11-28', ['now' => $now]];
        yield 'age: born today' => [0, new AgeRule(), '2026-11-28', ['now' => $now]];
        yield 'age: born this very second' => [0, new AgeRule(), '2026-11-28 12:00:00', ['from' => 'Y-m-d H:i:s', 'now' => $now]];
        yield 'age: other format' => [36, new AgeRule(), '07/03/1990', ['from' => 'd/m/Y', 'now' => $now]];
        yield 'age: a future date kept' => ['2030-01-01', new AgeRule(), '2030-01-01', ['now' => $now]];
        yield 'age: impossible date kept' => ['1990-02-30', new AgeRule(), '1990-02-30', ['now' => $now]];
        yield 'age: not a string' => [1990, new AgeRule(), 1990, ['now' => $now]];
        yield 'relative_date: just now' => ['just now', new RelativeDateRule(), '2026-11-28 11:59:01', ['now' => $now]];
        yield 'relative_date: one minute' => ['1 minute ago', new RelativeDateRule(), '2026-11-28 11:59:00', ['now' => $now]];
        yield 'relative_date: minutes' => ['5 minutes ago', new RelativeDateRule(), '2026-11-28 11:55:00', ['now' => $now]];
        yield 'relative_date: hours' => ['2 hours ago', new RelativeDateRule(), '2026-11-28 09:30:00', ['now' => $now]];
        yield 'relative_date: one day' => ['1 day ago', new RelativeDateRule(), '2026-11-27 12:00:00', ['now' => $now]];
        yield 'relative_date: in the future' => ['3 days from now', new RelativeDateRule(), '2026-12-01 12:00:00', ['now' => $now]];
        yield 'relative_date: months' => ['2 months ago', new RelativeDateRule(), '2026-09-28 12:00:00', ['now' => $now]];
        yield 'relative_date: one year' => ['1 year ago', new RelativeDateRule(), '2025-11-28 12:00:00', ['now' => $now]];
        yield 'relative_date: years' => ['10 years from now', new RelativeDateRule(), '2036-11-30 12:00:00', ['now' => $now]];
        yield 'relative_date: in a zone' => ['3 hours ago', new RelativeDateRule(), '2026-11-28 06:00:00', ['now' => $now, 'timezone' => 'America/Fortaleza']];
        yield 'relative_date: impossible date kept' => ['2026-02-30 00:00:00', new RelativeDateRule(), '2026-02-30 00:00:00', ['now' => $now]];
        yield 'relative_date: not a string' => [false, new RelativeDateRule(), false, ['now' => $now]];

        yield 'currency_format: default' => ['1,234,567.00', new CurrencyFormatRule(), '1234567'];
        yield 'currency_format: real' => ['-R$ 1.234,50', new CurrencyFormatRule(), -1234.5, ['prefix' => 'R$ ', 'dec_point' => ',', 'thousands' => '.']];
        yield 'currency_format: euro' => ['1.234,56 €', new CurrencyFormatRule(), 1234.555, ['dec_point' => ',', 'thousands' => '.', 'suffix' => ' €']];
        yield 'currency_format: no decimals' => ['1,235', new CurrencyFormatRule(), 1234.5, ['decimals' => 0]];
        yield 'currency_format: rounds to zero without a sign' => ['0.00', new CurrencyFormatRule(), -0.001];
        yield 'currency_format: zero' => ['$0.00', new CurrencyFormatRule(), 0, ['prefix' => '$']];
        yield 'currency_format: smallest negative amount shown' => ['-0.01', new CurrencyFormatRule(), -0.005];
        yield 'currency_format: not a number' => ['x', new CurrencyFormatRule(), 'x'];
        yield 'percentage: ratio' => ['12.50%', new PercentageRule(), 0.125];
        yield 'percentage: no decimals' => ['13%', new PercentageRule(), 0.125, ['decimals' => 0]];
        yield 'percentage: zero' => ['0.00%', new PercentageRule(), 0];
        yield 'percentage: numeric string' => ['5.0%', new PercentageRule(), '0.05', ['decimals' => 1]];
        yield 'percentage: Brazilian' => ['1.250,5 %', new PercentageRule(), 12.505, ['decimals' => 1, 'dec_point' => ',', 'thousands' => '.', 'suffix' => ' %']];
        yield 'percentage: negative' => ['-50.00%', new PercentageRule(), -0.5];
        yield 'percentage: rounds to zero without a sign' => ['0.00%', new PercentageRule(), -0.00001];
        yield 'percentage: not a number' => [true, new PercentageRule(), true];
        yield 'ordinal: 1st' => ['1st', new OrdinalRule(), 1];
        yield 'ordinal: 2nd' => ['2nd', new OrdinalRule(), 2];
        yield 'ordinal: 3rd' => ['3rd', new OrdinalRule(), '3'];
        yield 'ordinal: 4th' => ['4th', new OrdinalRule(), 4];
        yield 'ordinal: 11th' => ['11th', new OrdinalRule(), 11];
        yield 'ordinal: 12th' => ['12th', new OrdinalRule(), 12];
        yield 'ordinal: 13th' => ['13th', new OrdinalRule(), 13];
        yield 'ordinal: 21st' => ['21st', new OrdinalRule(), 21];
        yield 'ordinal: 22nd' => ['22nd', new OrdinalRule(), 22];
        yield 'ordinal: 23rd' => ['23rd', new OrdinalRule(), 23];
        yield 'ordinal: 101st' => ['101st', new OrdinalRule(), 101];
        yield 'ordinal: 111th' => ['111th', new OrdinalRule(), 111];
        yield 'ordinal: 112th' => ['112th', new OrdinalRule(), 112.0];
        yield 'ordinal: 0th' => ['0th', new OrdinalRule(), 0];
        yield 'ordinal: negative' => ['-1st', new OrdinalRule(), -1];
        yield 'ordinal: fraction kept' => [1.5, new OrdinalRule(), 1.5];
        yield 'ordinal: not a number' => ['first', new OrdinalRule(), 'first'];
        yield 'number_to_words: zero' => ['zero', new NumberToWordsRule(), 0];
        yield 'number_to_words: units' => ['seven', new NumberToWordsRule(), 7];
        yield 'number_to_words: teens' => ['nineteen', new NumberToWordsRule(), 19];
        yield 'number_to_words: tens' => ['twenty', new NumberToWordsRule(), 20];
        yield 'number_to_words: hyphen' => ['forty-two', new NumberToWordsRule(), '42'];
        yield 'number_to_words: hundred' => ['one hundred', new NumberToWordsRule(), 100];
        yield 'number_to_words: hundred and' => ['one hundred and fifteen', new NumberToWordsRule(), 115];
        yield 'number_to_words: largest group' => ['nine hundred and ninety-nine', new NumberToWordsRule(), 999];
        yield 'number_to_words: thousand' => ['one thousand', new NumberToWordsRule(), 1000];
        yield 'number_to_words: thousand and one' => ['one thousand and one', new NumberToWordsRule(), 1001];
        yield 'number_to_words: thousand ninety-nine' => ['one thousand and ninety-nine', new NumberToWordsRule(), 1099];
        yield 'number_to_words: thousand one hundred' => ['one thousand one hundred', new NumberToWordsRule(), 1100];
        yield 'number_to_words: thousand two hundred and' => ['one thousand two hundred and thirty-four', new NumberToWordsRule(), 1234];
        yield 'number_to_words: million and one' => ['one million and one', new NumberToWordsRule(), 1_000_001];
        yield 'number_to_words: millions' => ['two million five hundred thousand', new NumberToWordsRule(), 2_500_000];
        yield 'number_to_words: negative' => ['minus two million five hundred thousand', new NumberToWordsRule(), -2_500_000];
        yield 'number_to_words: billions' => ['three billion and seven', new NumberToWordsRule(), 3_000_000_007];
        yield 'number_to_words: whole float' => ['twelve', new NumberToWordsRule(), 12.0];
        yield 'number_to_words: largest int' => ['nine quintillion two hundred and twenty-three quadrillion three hundred and seventy-two trillion thirty-six billion eight hundred and fifty-four million seven hundred and seventy-five thousand eight hundred and seven', new NumberToWordsRule(), PHP_INT_MAX];
        yield 'number_to_words: smallest int kept' => [PHP_INT_MIN, new NumberToWordsRule(), PHP_INT_MIN];
        yield 'number_to_words: fraction kept' => [2.5, new NumberToWordsRule(), 2.5];
        yield 'number_to_words: not a number' => ['ten', new NumberToWordsRule(), 'ten'];
    }

    /**
     * @param array<string, mixed> $parameters
     */
    #[Test]
    #[DataProvider('cases')]
    public function rulesProduceTheirOutput(mixed $expected, TransformationRule $rule, mixed $value, array $parameters = []): void
    {
        self::assertTransforms($expected, $rule, $value, $parameters);
    }

    #[Test]
    public function datesWithoutAClockUseTheCurrentTime(): void
    {
        $tenYearsAgo = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('-10 years -1 day')->format('Y-m-d');
        $earlier = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify('-2 hours -1 minute')->format('Y-m-d H:i:s');

        self::assertSame(10, (new AgeRule())->transform($tenYearsAgo, self::context()));
        self::assertSame('2 hours ago', (new RelativeDateRule())->transform($earlier, self::context()));
    }

    #[Test]
    public function rulesNameThemselves(): void
    {
        $names = array_map(static fn(TransformationRule $rule): string => $rule->name(), [
            new DateToTimestampRule(), new DateToIso8601Rule(), new AgeRule(), new RelativeDateRule(),
            new CurrencyFormatRule(), new PercentageRule(), new OrdinalRule(), new NumberToWordsRule(),
        ]);

        self::assertSame([
            'date.to_timestamp', 'date.to_iso8601', 'date.age', 'date.relative',
            'numeric.currency_format', 'numeric.percentage', 'numeric.ordinal', 'numeric.number_to_words',
        ], $names);
    }

    /**
     * @return iterable<string, array{TransformerCode, TransformationRule, array<string, mixed>, non-empty-string}>
     */
    public static function misconfigurations(): iterable
    {
        $invalid = TransformerCode::InvalidRuleParameter;
        yield 'date_to_timestamp format not a string' => [$invalid, new DateToTimestampRule(), ['format' => 1], 'must be a string, int given.'];
        yield 'date_to_timestamp unknown time zone' => [$invalid, new DateToTimestampRule(), ['timezone' => 'Mars/Olympus'], "must be a time zone identifier, 'Mars/Olympus' given."];
        yield 'date_to_iso8601 empty time zone' => [$invalid, new DateToIso8601Rule(), ['timezone' => ''], 'must be a time zone identifier.'];
        yield 'date_to_iso8601 from not a string' => [$invalid, new DateToIso8601Rule(), ['from' => ['d/m/Y']], 'must be a string, array given.'];
        yield 'age now not a date' => [$invalid, new AgeRule(), ['now' => '2026-11-28'], 'must be a DateTimeInterface, string given.'];
        yield 'age from not a string' => [$invalid, new AgeRule(), ['from' => false], 'must be a string, bool given.'];
        yield 'relative_date now not a date' => [$invalid, new RelativeDateRule(), ['now' => 1_795_824_000], 'must be a DateTimeInterface, int given.'];
        yield 'currency_format decimals negative' => [$invalid, new CurrencyFormatRule(), ['decimals' => -1], 'must be an integer of 0 or more, -1 given.'];
        yield 'currency_format prefix not a string' => [$invalid, new CurrencyFormatRule(), ['prefix' => 1], 'must be a string, int given.'];
        yield 'currency_format suffix not a string' => [$invalid, new CurrencyFormatRule(), ['suffix' => null], 'must be a string, null given.'];
        yield 'percentage decimals negative' => [$invalid, new PercentageRule(), ['decimals' => -1], 'must be an integer of 0 or more, -1 given.'];
        yield 'percentage decimals not an integer' => [$invalid, new PercentageRule(), ['decimals' => 1.0], 'must be an integer, float given.'];
        yield 'percentage thousands not a string' => [$invalid, new PercentageRule(), ['thousands' => 0], 'must be a string, int given.'];
    }

    /**
     * Misconfiguration is reported whatever the value, so a wrong rule
     * cannot hide behind input of another type.
     *
     * @param array<string, mixed> $parameters
     * @param non-empty-string     $ending
     */
    #[Test]
    #[DataProvider('misconfigurations')]
    public function misconfiguredRulesThrow(TransformerCode $code, TransformationRule $rule, array $parameters, string $ending): void
    {
        self::assertRefused($code, $rule, $parameters);

        $this->expectException(InvalidRuleException::class);
        $this->expectExceptionMessageMatches('/' . preg_quote($ending, '/') . '\z/');
        $rule->transform(12, self::context($parameters));
    }
}
