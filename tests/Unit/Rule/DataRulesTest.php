<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Tests\Unit\Rule;

use KaririCode\Transformer\Contract\TransformationRule;
use KaririCode\Transformer\Exception\InvalidRuleException;
use KaririCode\Transformer\Exception\TransformerCode;
use KaririCode\Transformer\Rule\Data\ArrayToKeyValueRule;
use KaririCode\Transformer\Rule\Data\CsvToArrayRule;
use KaririCode\Transformer\Rule\Data\ImplodeRule;
use KaririCode\Transformer\Rule\Data\JsonDecodeRule;
use KaririCode\Transformer\Rule\Data\JsonEncodeRule;
use KaririCode\Transformer\Rule\Encoding\Base64DecodeRule;
use KaririCode\Transformer\Rule\Encoding\Base64EncodeRule;
use KaririCode\Transformer\Rule\Encoding\HashRule;
use KaririCode\Transformer\Rule\Structure\FlattenRule;
use KaririCode\Transformer\Rule\Structure\GroupByRule;
use KaririCode\Transformer\Rule\Structure\PluckRule;
use KaririCode\Transformer\Rule\Structure\RenameKeysRule;
use KaririCode\Transformer\Rule\Structure\UnflattenRule;
use KaririCode\Transformer\Tests\Support\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

// ── Unit: DataRulesTest ──────────────────────────────────────────────
// Data, encoding and structure rules: lossless or untouched.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(JsonEncodeRule::class)]
#[CoversClass(JsonDecodeRule::class)]
#[CoversClass(CsvToArrayRule::class)]
#[CoversClass(ArrayToKeyValueRule::class)]
#[CoversClass(ImplodeRule::class)]
#[CoversClass(Base64EncodeRule::class)]
#[CoversClass(Base64DecodeRule::class)]
#[CoversClass(HashRule::class)]
#[CoversClass(FlattenRule::class)]
#[CoversClass(UnflattenRule::class)]
#[CoversClass(PluckRule::class)]
#[CoversClass(GroupByRule::class)]
#[CoversClass(RenameKeysRule::class)]
final class DataRulesTest extends RuleTestCase
{
    /**
     * @return iterable<string, array{mixed, TransformationRule, mixed, 3?: array<string, mixed>}>
     */
    public static function cases(): iterable
    {
        yield 'json_encode: map, unescaped' => ['{"a":1.0,"b":"é/x"}', new JsonEncodeRule(), ['a' => 1.0, 'b' => 'é/x']];
        yield 'json_encode: list' => ['[1,2]', new JsonEncodeRule(), [1, 2]];
        yield 'json_encode: string' => ['"abc"', new JsonEncodeRule(), 'abc'];
        yield 'json_encode: null' => ['null', new JsonEncodeRule(), null];
        yield 'json_encode: flags' => ["{\n    \"a\": 1\n}", new JsonEncodeRule(), ['a' => 1], ['flags' => JSON_PRETTY_PRINT]];
        yield 'json_encode: invalid UTF-8 kept' => ["\xff", new JsonEncodeRule(), "\xff"];
        yield 'json_encode: infinity kept' => [INF, new JsonEncodeRule(), INF];
        yield 'json_encode: too deep kept' => [[[1]], new JsonEncodeRule(), [[1]], ['depth' => 1]];
        yield 'json_decode: objects as arrays' => [['a' => ['b' => 1]], new JsonDecodeRule(), '{"a":{"b":1}}'];
        yield 'json_decode: big integers keep their digits' => [['big' => '12345678901234567890', 'f' => 1.5], new JsonDecodeRule(), '{"big": 12345678901234567890, "f": 1.5}'];
        yield 'json_decode: big integers as floats when asked' => [1.2345678901234567E+19, new JsonDecodeRule(), '12345678901234567890', ['flags' => 0]];
        yield 'json_decode: list' => [[1, 2], new JsonDecodeRule(), ' [1, 2] '];
        yield 'json_decode: the null literal' => [null, new JsonDecodeRule(), 'null'];
        yield 'json_decode: not JSON' => ['not json', new JsonDecodeRule(), 'not json'];
        yield 'json_decode: empty' => ['', new JsonDecodeRule(), ''];
        yield 'json_decode: too deep kept' => ['[[1]]', new JsonDecodeRule(), '[[1]]', ['depth' => 1]];
        yield 'json_decode: not a string' => [5, new JsonDecodeRule(), 5];

        yield 'csv_to_array: header and quoted fields' => [[['name' => 'Ana', 'note' => 'a, b'], ['name' => 'Bia', 'note' => "say \"hi\"\nthere"]], new CsvToArrayRule(), "name,note\r\nAna,\"a, b\"\r\nBia,\"say \"\"hi\"\"\nthere\"\r\n"];
        yield 'csv_to_array: separator, BOM, blank lines' => [[['a' => '1', 'b' => '2'], ['a' => '3', 'b' => '4']], new CsvToArrayRule(), "\u{FEFF}a;b\n1;2\n\n3;4", ['separator' => ';']];
        yield 'csv_to_array: enclosure' => [[['a' => 'x,y']], new CsvToArrayRule(), "a\n'x,y'", ['enclosure' => "'"]];
        yield 'csv_to_array: header only' => [[], new CsvToArrayRule(), 'a,b'];
        yield 'csv_to_array: empty' => [[], new CsvToArrayRule(), ''];
        yield 'csv_to_array: numeric names' => [[[1 => 'x', 2 => 'y']], new CsvToArrayRule(), "1,2\nx,y"];
        yield 'csv_to_array: without header' => [[['x', 'y'], ['1', '2', '3']], new CsvToArrayRule(), "x,y\n1,2,3", ['header' => false]];
        yield 'csv_to_array: a record wider than the header' => ["a,b\n1,2,3", new CsvToArrayRule(), "a,b\n1,2,3"];
        yield 'csv_to_array: a record narrower than the header' => ["a,b\n1", new CsvToArrayRule(), "a,b\n1"];
        yield 'csv_to_array: repeated names' => ["a,a\n1,2", new CsvToArrayRule(), "a,a\n1,2"];
        yield 'csv_to_array: enclosure left open' => ["a,b\n1,\"2", new CsvToArrayRule(), "a,b\n1,\"2"];
        yield 'csv_to_array: not a string' => [['a'], new CsvToArrayRule(), ['a']];
        yield 'array_to_key_value: later keys win' => [[1 => 'C', 2 => 'B'], new ArrayToKeyValueRule(), [['id' => 1, 'name' => 'A'], ['id' => 2, 'name' => 'B'], ['id' => 1, 'name' => 'C']]];
        yield 'array_to_key_value: fields given' => [['BR' => 'Brasil'], new ArrayToKeyValueRule(), [['code' => 'BR', 'label' => 'Brasil']], ['key' => 'code', 'value' => 'label']];
        yield 'array_to_key_value: null values kept' => [['x' => null], new ArrayToKeyValueRule(), [['id' => 'x', 'name' => null]]];
        yield 'array_to_key_value: empty' => [[], new ArrayToKeyValueRule(), []];
        yield 'array_to_key_value: a record without the key' => [[['id' => 1, 'name' => 'A'], ['name' => 'B']], new ArrayToKeyValueRule(), [['id' => 1, 'name' => 'A'], ['name' => 'B']]];
        yield 'array_to_key_value: a record without the value' => [[['id' => 1]], new ArrayToKeyValueRule(), [['id' => 1]]];
        yield 'array_to_key_value: a key that is a float' => [[['id' => 1.5, 'name' => 'A']], new ArrayToKeyValueRule(), [['id' => 1.5, 'name' => 'A']]];
        yield 'array_to_key_value: a record that is not an array' => [['x'], new ArrayToKeyValueRule(), ['x']];
        yield 'array_to_key_value: not an array' => ['x', new ArrayToKeyValueRule(), 'x'];
        yield 'implode: scalars' => ['1|2.5|x', new ImplodeRule(), [1, 2.5, 'x'], ['separator' => '|']];
        yield 'implode: keys ignored' => ['x,y', new ImplodeRule(), ['a' => 'x', 'b' => 'y']];
        yield 'implode: Stringable' => ['text,1', new ImplodeRule(), [new class implements \Stringable {
            public function __toString(): string
            {
                return 'text';
            }
        }, 1]];
        yield 'implode: empty' => ['', new ImplodeRule(), []];
        yield 'implode: nested list kept' => [[1, [2]], new ImplodeRule(), [1, [2]]];
        yield 'implode: boolean kept' => [[true, 'x'], new ImplodeRule(), [true, 'x']];
        yield 'implode: null kept' => [['x', null], new ImplodeRule(), ['x', null]];
        yield 'implode: not an array' => ['x', new ImplodeRule(), 'x'];

        yield 'base64_encode: text' => ['YWJj', new Base64EncodeRule(), 'abc'];
        yield 'base64_encode: bytes' => ['AP8=', new Base64EncodeRule(), "\x00\xff"];
        yield 'base64_encode: empty' => ['', new Base64EncodeRule(), ''];
        yield 'base64_encode: not a string' => [5, new Base64EncodeRule(), 5];
        yield 'base64_decode: text' => ['abc', new Base64DecodeRule(), 'YWJj'];
        yield 'base64_decode: bytes' => ["\x00\xff", new Base64DecodeRule(), 'AP8='];
        yield 'base64_decode: whitespace ignored' => ['abc', new Base64DecodeRule(), "YW J\nj"];
        yield 'base64_decode: padding left out' => ['a', new Base64DecodeRule(), 'YQ'];
        yield 'base64_decode: unused bits set' => ['YR==', new Base64DecodeRule(), 'YR=='];
        yield 'base64_decode: a group cut short' => ['YWJ', new Base64DecodeRule(), 'YWJ'];
        yield 'base64_decode: outside the alphabet' => ['!!!', new Base64DecodeRule(), '!!!'];
        yield 'base64_decode: empty' => ['', new Base64DecodeRule(), ''];
        yield 'base64_decode: not a string' => [null, new Base64DecodeRule(), null];
        yield 'hash: sha256 by default' => ['ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad', new HashRule(), 'abc'];
        yield 'hash: md5' => ['900150983cd24fb0d6963f7d28e17f72', new HashRule(), 'abc', ['algo' => 'md5']];
        yield 'hash: any letter case' => ['a9993e364706816aba3e25717850c26c9cd0d89d', new HashRule(), 'abc', ['algo' => 'SHA1']];
        yield 'hash: not a string' => [42, new HashRule(), 42];

        yield 'flatten: nested, empty arrays kept' => [['a.b' => 1, 'a.c' => [], 'd' => 2], new FlattenRule(), ['a' => ['b' => 1, 'c' => []], 'd' => 2]];
        yield 'flatten: separator' => [['a/b/c' => 1], new FlattenRule(), ['a' => ['b' => ['c' => 1]]], ['separator' => '/']];
        yield 'flatten: lists' => [['0.0' => 1, '0.1' => 2, 1 => 3], new FlattenRule(), [[1, 2], 3]];
        yield 'flatten: the empty key' => [['.a' => 1], new FlattenRule(), ['' => ['a' => 1]]];
        yield 'flatten: empty' => [[], new FlattenRule(), []];
        yield 'flatten: colliding paths kept' => [['a.b' => 1, 'a' => ['b' => 2]], new FlattenRule(), ['a.b' => 1, 'a' => ['b' => 2]]];
        yield 'flatten: deep collision kept' => [['a' => ['b.c' => 1, 'b' => ['c' => 2]]], new FlattenRule(), ['a' => ['b.c' => 1, 'b' => ['c' => 2]]]];
        yield 'flatten: not an array' => ['a.b', new FlattenRule(), 'a.b'];
        yield 'unflatten: nested' => [['a' => ['b' => 1, 'c' => 2], 'd' => 3], new UnflattenRule(), ['a.b' => 1, 'a.c' => 2, 'd' => 3]];
        yield 'unflatten: separator' => [['a' => ['b' => 1]], new UnflattenRule(), ['a/b' => 1], ['separator' => '/']];
        yield 'unflatten: numeric segments' => [[0 => ['name' => 'x']], new UnflattenRule(), ['0.name' => 'x']];
        yield 'unflatten: integer keys kept' => [[5 => 'a', 7 => ['x' => 'b']], new UnflattenRule(), [5 => 'a', '7.x' => 'b']];
        yield 'unflatten: siblings kept' => [['x' => 1, 'a' => ['b' => 2]], new UnflattenRule(), ['x' => 1, 'a.b' => 2]];
        yield 'unflatten: merged into an array value' => [['a' => ['x' => 1, 'b' => 2]], new UnflattenRule(), ['a' => ['x' => 1], 'a.b' => 2]];
        yield 'unflatten: a value where a branch is needed' => [['a' => 1, 'a.b' => 2], new UnflattenRule(), ['a' => 1, 'a.b' => 2]];
        yield 'unflatten: a branch where a value goes' => [['a.b' => 1, 'a' => 2], new UnflattenRule(), ['a.b' => 1, 'a' => 2]];
        yield 'unflatten: one place twice' => [['a.b' => 1, 'a' => ['b' => 2]], new UnflattenRule(), ['a.b' => 1, 'a' => ['b' => 2]]];
        yield 'unflatten: not an array' => ['a', new UnflattenRule(), 'a'];
        yield 'pluck: dotted field' => [['a@x', null, null], new PluckRule(), [['contact' => ['email' => 'a@x']], ['x' => 1], 'no'], ['field' => 'contact.email']];
        yield 'pluck: keys renumbered' => [[1, 2], new PluckRule(), ['k' => ['id' => 1], 'j' => ['id' => 2]], ['field' => 'id']];
        yield 'pluck: not an array' => ['x', new PluckRule(), 'x', ['field' => 'id']];
        yield 'group_by: groups keep order' => [['a' => [['s' => 'a', 'n' => 1], ['s' => 'a', 'n' => 3]], 'b' => [['s' => 'b', 'n' => 2]]], new GroupByRule(), [['s' => 'a', 'n' => 1], ['s' => 'b', 'n' => 2], ['s' => 'a', 'n' => 3]], ['field' => 's']];
        yield 'group_by: dotted field' => [[7 => [['u' => ['id' => 7]]]], new GroupByRule(), [['u' => ['id' => 7]]], ['field' => 'u.id']];
        yield 'group_by: a record without the field' => [[['s' => 'a'], ['n' => 2]], new GroupByRule(), [['s' => 'a'], ['n' => 2]], ['field' => 's']];
        yield 'group_by: a float key' => [[['s' => 1.5]], new GroupByRule(), [['s' => 1.5]], ['field' => 's']];
        yield 'group_by: a record that is not an array' => [['x'], new GroupByRule(), ['x'], ['field' => 's']];
        yield 'group_by: not an array' => [null, new GroupByRule(), null, ['field' => 's']];
        yield 'rename_keys: swap' => [['b' => 1, 'a' => 2], new RenameKeysRule(), ['a' => 1, 'b' => 2], ['map' => ['a' => 'b', 'b' => 'a']]];
        yield 'rename_keys: order kept' => [['x' => 1, 'email' => 2, 'z' => 3], new RenameKeysRule(), ['x' => 1, 'e-mail' => 2, 'z' => 3], ['map' => ['e-mail' => 'email']]];
        yield 'rename_keys: integer keys' => [['ten' => 'a'], new RenameKeysRule(), [10 => 'a'], ['map' => [10 => 'ten']]];
        yield 'rename_keys: collision kept' => [['a' => 1, 'b' => 2], new RenameKeysRule(), ['a' => 1, 'b' => 2], ['map' => ['a' => 'b']]];
        yield 'rename_keys: not an array' => ['a', new RenameKeysRule(), 'a', ['map' => ['a' => 'b']]];
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
    public function objectsAreEncodedAndDecodedAsObjectsWhenAsked(): void
    {
        $object = new stdClass();
        $object->x = 1;

        self::assertSame('{"x":1}', (new JsonEncodeRule())->transform($object, self::context()));
        self::assertEquals($object, (new JsonDecodeRule())->transform('{"x":1}', self::context(['assoc' => false])));
    }

    #[Test]
    public function rulesNameThemselves(): void
    {
        $names = array_map(static fn(TransformationRule $rule): string => $rule->name(), [
            new JsonEncodeRule(), new JsonDecodeRule(), new CsvToArrayRule(), new ArrayToKeyValueRule(), new ImplodeRule(),
            new Base64EncodeRule(), new Base64DecodeRule(), new HashRule(),
            new FlattenRule(), new UnflattenRule(), new PluckRule(), new GroupByRule(), new RenameKeysRule(),
        ]);

        self::assertSame([
            'data.json_encode', 'data.json_decode', 'data.csv_to_array', 'data.array_to_key_value', 'data.implode',
            'encoding.base64_encode', 'encoding.base64_decode', 'encoding.hash',
            'structure.flatten', 'structure.unflatten', 'structure.pluck', 'structure.group_by', 'structure.rename_keys',
        ], $names);
    }

    /**
     * @return iterable<string, array{TransformerCode, TransformationRule, array<string, mixed>, non-empty-string}>
     */
    public static function misconfigurations(): iterable
    {
        $invalid = TransformerCode::InvalidRuleParameter;
        $missing = TransformerCode::MissingRuleParameter;
        yield 'json_encode flags not an integer' => [$invalid, new JsonEncodeRule(), ['flags' => 'x'], 'must be an integer, string given.'];
        yield 'json_encode partial output' => [$invalid, new JsonEncodeRule(), ['flags' => JSON_PARTIAL_OUTPUT_ON_ERROR], 'must be flags without JSON_PARTIAL_OUTPUT_ON_ERROR, which writes null for what it cannot encode.'];
        yield 'json_encode depth zero' => [$invalid, new JsonEncodeRule(), ['depth' => 0], 'must be an integer from 1 to 2147483647, 0 given.'];
        yield 'json_decode assoc not a boolean' => [$invalid, new JsonDecodeRule(), ['assoc' => 1], 'must be a boolean, int given.'];
        yield 'json_decode depth negative' => [$invalid, new JsonDecodeRule(), ['depth' => -1], 'must be an integer from 1 to 2147483647, -1 given.'];
        yield 'json_decode flags not an integer' => [$invalid, new JsonDecodeRule(), ['flags' => true], 'must be an integer, bool given.'];
        yield 'csv separator of two characters' => [$invalid, new CsvToArrayRule(), ['separator' => ';;'], "must be one single-byte character other than a line break, ';;' given."];
        yield 'csv separator a line break' => [$invalid, new CsvToArrayRule(), ['separator' => "\n"], 'must be one single-byte character other than a line break, \'\\n\' given.'];
        yield 'csv enclosure a carriage return' => [$invalid, new CsvToArrayRule(), ['enclosure' => "\r"], 'must be one single-byte character other than a line break, \'\\r\' given.'];
        yield 'csv multibyte separator' => [$invalid, new CsvToArrayRule(), ['separator' => '；'], "must be one single-byte character other than a line break, '；' given."];
        yield 'csv enclosure equal to the separator' => [$invalid, new CsvToArrayRule(), ['enclosure' => ','], 'must be a character other than the separator.'];
        yield 'csv header not a boolean' => [$invalid, new CsvToArrayRule(), ['header' => 'yes'], 'must be a boolean, string given.'];
        yield 'array_to_key_value key not a string' => [$invalid, new ArrayToKeyValueRule(), ['key' => 1], 'must be a string, int given.'];
        yield 'implode separator not a string' => [$invalid, new ImplodeRule(), ['separator' => []], 'must be a string, array given.'];
        yield 'hash with an unknown algorithm' => [$invalid, new HashRule(), ['algo' => 'SHA-256'], "must be an algorithm hash_algos() lists, 'SHA-256' given."];
        yield 'flatten with an empty separator' => [$invalid, new FlattenRule(), ['separator' => ''], 'must be a non-empty string.'];
        yield 'unflatten with an empty separator' => [$invalid, new UnflattenRule(), ['separator' => ''], 'must be a non-empty string.'];
        yield 'pluck without field' => [$missing, new PluckRule(), [], 'needs the parameter "field".'];
        yield 'group_by field not a string' => [$invalid, new GroupByRule(), ['field' => 1], 'must be a string, int given.'];
        yield 'rename_keys without map' => [$missing, new RenameKeysRule(), [], 'needs the parameter "map".'];
        yield 'rename_keys with an empty map' => [$invalid, new RenameKeysRule(), ['map' => []], 'must be a non-empty map of keys to new names.'];
        yield 'rename_keys with a name that is an array' => [$invalid, new RenameKeysRule(), ['map' => ['a' => ['b']]], 'must be a non-empty map of keys to new names.'];
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
