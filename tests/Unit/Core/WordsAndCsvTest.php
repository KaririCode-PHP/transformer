<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Tests\Unit\Core;

use KaririCode\Transformer\Core\CsvReader;
use KaririCode\Transformer\Core\Words;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// ── Unit: WordsAndCsvTest ────────────────────────────────────────────
// The word splitter of the case rules and the RFC 4180 reader.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(Words::class)]
#[CoversClass(CsvReader::class)]
final class WordsAndCsvTest extends TestCase
{
    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function phrases(): iterable
    {
        yield 'spaces' => ['hello world', ['hello', 'world']];
        yield 'mixed separators' => ['user_first-name.last  name', ['user', 'first', 'name', 'last', 'name']];
        yield 'camelCase' => ['helloWorld', ['hello', 'World']];
        yield 'PascalCase' => ['HelloWorld', ['Hello', 'World']];
        yield 'acronym first' => ['HTTPServerError', ['HTTP', 'Server', 'Error']];
        yield 'acronym inside' => ['getHTTPResponse', ['get', 'HTTP', 'Response']];
        yield 'acronym last' => ['parseURL', ['parse', 'URL']];
        yield 'digits stay with the word' => ['user2Name', ['user2', 'Name']];
        yield 'digits alone' => ['version 2 beta', ['version', '2', 'beta']];
        yield 'accented letters' => ['São Paulo', ['São', 'Paulo']];
        yield 'accented camelCase' => ['açãoÉpica', ['ação', 'Épica']];
        yield 'combining marks' => ["cafe\u{301}Bar", ["cafe\u{301}", 'Bar']];
        yield 'letters without case' => ['東京 タワー', ['東京', 'タワー']];
        yield 'nothing but separators' => [' -_. ', []];
        yield 'empty' => ['', []];
        yield 'invalid UTF-8' => ["a\xffb", ['a', 'b']];
    }

    /**
     * @param list<string> $words
     */
    #[Test]
    #[DataProvider('phrases')]
    public function phrasesSplitIntoWords(string $phrase, array $words): void
    {
        self::assertSame($words, Words::of($phrase));
    }

    #[Test]
    public function capitalizingTitleCasesTheFirstLetterOnly(): void
    {
        self::assertSame('Hello', Words::capitalize('hELLO'));
        self::assertSame('Élan', Words::capitalize('élan'));
        self::assertSame('Http', Words::capitalize('HTTP'));
        self::assertSame('Ação', Words::capitalize('AÇÃO'));
        self::assertSame("\u{01C5}ungla", Words::capitalize("\u{01C6}ungla"));
        self::assertSame('2fa', Words::capitalize('2FA'));
        self::assertSame('', Words::capitalize(''));
    }

    /**
     * @return iterable<string, array{string, list<list<string>>}>
     */
    public static function documents(): iterable
    {
        yield 'empty' => ['', []];
        yield 'one field' => ['a', [['a']]];
        yield 'CRLF records' => ["a,b\r\n1,2\r\n", [['a', 'b'], ['1', '2']]];
        yield 'LF records' => ["a,b\n1,2", [['a', 'b'], ['1', '2']]];
        yield 'CR records' => ["a,b\r1,2\r", [['a', 'b'], ['1', '2']]];
        yield 'blank lines skipped' => ["\n\na\r\n\r\nb\n", [['a'], ['b']]];
        yield 'empty fields' => [",a,\n,,", [['', 'a', ''], ['', '', '']]];
        yield 'trailing separator at the end' => ['a,', [['a', '']]];
        yield 'quoted separator' => ['"a,b",c', [['a,b', 'c']]];
        yield 'quoted line breaks' => ["\"line 1\r\nline 2\",x", [["line 1\r\nline 2", 'x']]];
        yield 'doubled enclosure' => ['"say ""hi""",""""', [['say "hi"', '"']]];
        yield 'quoted empty field' => ["\"\"\n", [['']]];
        yield 'quoted field at the end' => ['a,"b"', [['a', 'b']]];
        yield 'enclosure inside an unquoted field' => ['5" screen,x', [['5" screen', 'x']]];
        yield 'byte order mark dropped' => ["\u{FEFF}name\nAna", [['name'], ['Ana']]];
        yield 'ragged records kept' => ["a,b\n1", [['a', 'b'], ['1']]];
        yield 'empty field before a line break' => ["a,\nb", [['a', ''], ['b']]];
        yield 'mixed line endings' => ["a\r\n\rb\n\r\nc", [['a'], ['b'], ['c']]];
    }

    /**
     * @param list<list<string>> $records
     */
    #[Test]
    #[DataProvider('documents')]
    public function csvIsReadAsRfc4180Records(string $csv, array $records): void
    {
        self::assertSame($records, CsvReader::records(csv: $csv, separator: ',', enclosure: '"'));
    }

    #[Test]
    public function otherSeparatorsAndEnclosuresWork(): void
    {
        self::assertSame([['a', 'b;c'], ['1', '2']], CsvReader::records(csv: "a;'b;c'\n1;2", separator: ';', enclosure: "'"));
        self::assertSame([['a', 'b']], CsvReader::records(csv: "a\tb", separator: "\t", enclosure: '"'));
    }

    #[Test]
    public function textThatIsNotCsvGivesNull(): void
    {
        self::assertNull(CsvReader::records(csv: 'a,"b', separator: ',', enclosure: '"'));
        self::assertNull(CsvReader::records(csv: '"a"b,c', separator: ',', enclosure: '"'));
        self::assertNull(CsvReader::records(csv: "\"a\"\"", separator: ',', enclosure: '"'));
    }
}
