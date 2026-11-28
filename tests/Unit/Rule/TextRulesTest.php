<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Tests\Unit\Rule;

use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Exception\InvalidRuleException;
use KaririCode\Transformer\Exception\TransformerCode;
use KaririCode\Transformer\Rule\Brazilian\CepToDigitsRule;
use KaririCode\Transformer\Rule\Brazilian\CnpjToDigitsRule;
use KaririCode\Transformer\Rule\Brazilian\CpfToDigitsRule;
use KaririCode\Transformer\Rule\Brazilian\PhoneFormatRule;
use KaririCode\Transformer\Rule\String\CamelCaseRule;
use KaririCode\Transformer\Rule\String\KebabCaseRule;
use KaririCode\Transformer\Rule\String\MaskRule;
use KaririCode\Transformer\Rule\String\PascalCaseRule;
use KaririCode\Transformer\Rule\String\RepeatRule;
use KaririCode\Transformer\Rule\String\ReverseRule;
use KaririCode\Transformer\Rule\String\SnakeCaseRule;
use KaririCode\Transformer\Tests\Support\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

// ── Unit: TextRulesTest ──────────────────────────────────────────────
// The string and Brazilian document rules: exact output, other types untouched.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(CamelCaseRule::class)]
#[CoversClass(PascalCaseRule::class)]
#[CoversClass(SnakeCaseRule::class)]
#[CoversClass(KebabCaseRule::class)]
#[CoversClass(MaskRule::class)]
#[CoversClass(ReverseRule::class)]
#[CoversClass(RepeatRule::class)]
#[CoversClass(CpfToDigitsRule::class)]
#[CoversClass(CnpjToDigitsRule::class)]
#[CoversClass(CepToDigitsRule::class)]
#[CoversClass(PhoneFormatRule::class)]
final class TextRulesTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{mixed, TransformationRule, mixed, 3?: array<string, mixed>}>
     */
    public static function cases(): iterable
    {
        yield 'camel_case: words' => ['userFirstName', new CamelCaseRule(), 'user first name'];
        yield 'camel_case: snake' => ['userFirstName', new CamelCaseRule(), 'user_first_name'];
        yield 'camel_case: Pascal' => ['userFirstName', new CamelCaseRule(), 'UserFirstName'];
        yield 'camel_case: already camel' => ['helloWorld', new CamelCaseRule(), 'helloWorld'];
        yield 'camel_case: acronym' => ['httpServerError', new CamelCaseRule(), 'HTTPServerError'];
        yield 'camel_case: accented' => ['élanVital', new CamelCaseRule(), 'Élan vital'];
        yield 'camel_case: one word' => ['hello', new CamelCaseRule(), 'HELLO'];
        yield 'camel_case: no word' => ['', new CamelCaseRule(), ' -- '];
        yield 'camel_case: not a string' => [5, new CamelCaseRule(), 5];
        yield 'pascal_case: words' => ['UserFirstName', new PascalCaseRule(), 'user first_name'];
        yield 'pascal_case: camel' => ['HelloWorld', new PascalCaseRule(), 'helloWorld'];
        yield 'pascal_case: accented' => ['ÉlanVital', new PascalCaseRule(), 'élan vital'];
        yield 'pascal_case: not a string' => [null, new PascalCaseRule(), null];
        yield 'snake_case: camel' => ['user_first_name', new SnakeCaseRule(), 'userFirstName'];
        yield 'snake_case: acronyms' => ['get_http_response_code', new SnakeCaseRule(), 'getHTTPResponse code'];
        yield 'snake_case: kebab' => ['a_b_c', new SnakeCaseRule(), 'a-b-c'];
        yield 'snake_case: digits' => ['user2_name', new SnakeCaseRule(), 'user2Name'];
        yield 'snake_case: accented' => ['são_paulo', new SnakeCaseRule(), 'São Paulo'];
        yield 'snake_case: accented capitals' => ['élan_vital', new SnakeCaseRule(), 'ÉLAN VITAL'];
        yield 'snake_case: not a string' => [[], new SnakeCaseRule(), []];
        yield 'kebab_case: camel' => ['user-first-name', new KebabCaseRule(), 'userFirstName'];
        yield 'kebab_case: snake' => ['a-b', new KebabCaseRule(), 'A_B'];
        yield 'kebab_case: accented' => ['são-paulo', new KebabCaseRule(), 'São Paulo'];
        yield 'kebab_case: accented capitals' => ['ação-épica', new KebabCaseRule(), 'AÇÃO ÉPICA'];
        yield 'kebab_case: not a string' => [1.5, new KebabCaseRule(), 1.5];

        yield 'mask: middle by default' => ['ana****com', new MaskRule(), 'ana@xy.com'];
        yield 'mask: card' => ['************1111', new MaskRule(), '4111111111111111', ['keep_start' => 0, 'keep_end' => 4]];
        yield 'mask: nothing kept' => ['******', new MaskRule(), 'secret', ['keep_start' => 0, 'keep_end' => 0]];
        yield 'mask: start only' => ['se****', new MaskRule(), 'secret', ['keep_start' => 2, 'keep_end' => 0]];
        yield 'mask: too short to keep both ends' => ['***', new MaskRule(), 'abc'];
        yield 'mask: exactly both ends' => ['******', new MaskRule(), 'abcdef'];
        yield 'mask: one more than both ends' => ['abc*efg', new MaskRule(), 'abcdefg'];
        yield 'mask: characters, not bytes' => ['açã####ima', new MaskRule(), 'ação ótima', ['char' => '#']];
        yield 'mask: multibyte mask' => ['ab••', new MaskRule(), 'abcd', ['keep_start' => 2, 'keep_end' => 0, 'char' => '•']];
        yield 'mask: empty' => ['', new MaskRule(), ''];
        yield 'mask: not a string' => [1234, new MaskRule(), 1234];
        yield 'reverse: graphemes' => ["e\u{301} 👩\u{200D}💻 oãça", new ReverseRule(), "ação 👩\u{200D}💻 e\u{301}"];
        yield 'reverse: invalid UTF-8' => ['b?a', new ReverseRule(), "a\xffb"];
        yield 'reverse: not a string' => [true, new ReverseRule(), true];
        yield 'repeat: twice by default' => ['abab', new RepeatRule(), 'ab'];
        yield 'repeat: separator' => ['ab-ab-ab', new RepeatRule(), 'ab', ['times' => 3, 'separator' => '-']];
        yield 'repeat: once' => ['ab', new RepeatRule(), 'ab', ['times' => 1]];
        yield 'repeat: none' => ['', new RepeatRule(), 'ab', ['times' => 0]];
        yield 'repeat: not a string' => [7, new RepeatRule(), 7];

        yield 'cpf_to_digits: formatted' => ['12345678909', new CpfToDigitsRule(), '123.456.789-09'];
        yield 'cpf_to_digits: partly formatted' => ['12345678909', new CpfToDigitsRule(), '123456789-09'];
        yield 'cpf_to_digits: bare' => ['12345678909', new CpfToDigitsRule(), '12345678909'];
        yield 'cpf_to_digits: text around' => ['CPF 123.456.789-09', new CpfToDigitsRule(), 'CPF 123.456.789-09'];
        yield 'cpf_to_digits: ten digits' => ['1234567890', new CpfToDigitsRule(), '1234567890'];
        yield 'cpf_to_digits: trailing newline' => ["12345678909\n", new CpfToDigitsRule(), "12345678909\n"];
        yield 'cpf_to_digits: not a string' => [12345678909, new CpfToDigitsRule(), 12345678909];
        yield 'cnpj_to_digits: numeric' => ['11222333000181', new CnpjToDigitsRule(), '11.222.333/0001-81'];
        yield 'cnpj_to_digits: alphanumeric' => ['12ABC34501DE35', new CnpjToDigitsRule(), '12.ABC.345/01DE-35'];
        yield 'cnpj_to_digits: bare' => ['12ABC34501DE35', new CnpjToDigitsRule(), '12ABC34501DE35'];
        yield 'cnpj_to_digits: lower case' => ['12.abc.345/01de-35', new CnpjToDigitsRule(), '12.abc.345/01de-35'];
        yield 'cnpj_to_digits: letters in the check digits' => ['12ABC34501DEAB', new CnpjToDigitsRule(), '12ABC34501DEAB'];
        yield 'cnpj_to_digits: not a string' => [false, new CnpjToDigitsRule(), false];
        yield 'cep_to_digits: formatted' => ['63100000', new CepToDigitsRule(), '63100-000'];
        yield 'cep_to_digits: bare' => ['63100000', new CepToDigitsRule(), '63100000'];
        yield 'cep_to_digits: dotted' => ['63.100-000', new CepToDigitsRule(), '63.100-000'];
        yield 'cep_to_digits: not a string' => [63100000, new CepToDigitsRule(), 63100000];
        yield 'phone_format: mobile with +55' => ['(88) 99999-0000', new PhoneFormatRule(), '+55 (88) 99999-0000'];
        yield 'phone_format: landline digits' => ['(88) 3521-0000', new PhoneFormatRule(), '8835210000'];
        yield 'phone_format: dotted mobile' => ['(11) 98765-4321', new PhoneFormatRule(), '11.98765.4321'];
        yield 'phone_format: carrier prefix' => ['088 99999 0000', new PhoneFormatRule(), '088 99999 0000'];
        yield 'phone_format: area code starting with 0' => ['0889999000', new PhoneFormatRule(), '0889999000'];
        yield 'phone_format: nine digits' => ['999990000', new PhoneFormatRule(), '999990000'];
        yield 'phone_format: 55 without the plus' => ['5588999990000', new PhoneFormatRule(), '5588999990000'];
        yield 'phone_format: other characters' => ['tel 88 99999 0000', new PhoneFormatRule(), 'tel 88 99999 0000'];
        yield 'phone_format: not a string' => [8835210000, new PhoneFormatRule(), 8835210000];
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
    public function rulesNameThemselves(): void
    {
        $names = array_map(static fn(TransformationRule $rule): string => $rule->name(), [
            new CamelCaseRule(), new PascalCaseRule(), new SnakeCaseRule(), new KebabCaseRule(), new MaskRule(), new ReverseRule(), new RepeatRule(),
            new CpfToDigitsRule(), new CnpjToDigitsRule(), new CepToDigitsRule(), new PhoneFormatRule(),
        ]);

        self::assertSame([
            'string.camel_case', 'string.pascal_case', 'string.snake_case', 'string.kebab_case', 'string.mask', 'string.reverse', 'string.repeat',
            'brazilian.cpf_to_digits', 'brazilian.cnpj_to_digits', 'brazilian.cep_to_digits', 'brazilian.phone_format',
        ], $names);
    }

    /**
     * @return iterable<string, array{TransformerCode, TransformationRule, array<string, mixed>, non-empty-string}>
     */
    public static function misconfigurations(): iterable
    {
        $invalid = TransformerCode::InvalidRuleParameter;
        yield 'mask keep_start negative' => [$invalid, new MaskRule(), ['keep_start' => -1], 'must be an integer of 0 or more, -1 given.'];
        yield 'mask keep_end negative' => [$invalid, new MaskRule(), ['keep_end' => -1], 'must be an integer of 0 or more, -1 given.'];
        yield 'mask keep_end not an integer' => [$invalid, new MaskRule(), ['keep_end' => '4'], 'must be an integer, string given.'];
        yield 'mask char of two characters' => [$invalid, new MaskRule(), ['char' => '**'], "must be one character, '**' given."];
        yield 'mask char empty' => [$invalid, new MaskRule(), ['char' => ''], "must be one character, '' given."];
        yield 'repeat times negative' => [$invalid, new RepeatRule(), ['times' => -2], 'must be an integer of 0 or more, -2 given.'];
        yield 'repeat separator not a string' => [$invalid, new RepeatRule(), ['separator' => 0], 'must be a string, int given.'];
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
