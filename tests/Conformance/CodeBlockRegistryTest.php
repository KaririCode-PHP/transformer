<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Tests\Conformance;

use KaririCode\Exception\ErrorCodeTable;
use KaririCode\Transformer\Exception\TransformerCode;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * TransformerCode against the ecosystem registry of record, CODE-BLOCKS.md of
 * kariricode/exception, read from the installed package (ADR-004).
 */
#[CoversNothing]
final class CodeBlockRegistryTest extends TestCase
{
    private const string REGISTRY = __DIR__ . '/../../vendor/kariricode/exception/CODE-BLOCKS.md';

    private const int FIRST = 3400;

    private const int LAST = 3499;

    #[Test]
    public function transformerCodeIsAValidCodeTable(): void
    {
        $table = new ErrorCodeTable(TransformerCode::class);

        self::assertSame('TRF', $table->prefix);
        self::assertSame([self::FIRST, self::LAST], [$table->block->first, $table->block->last]);
    }

    /**
     * The installed registry may predate the claim (QD-V433 open items):
     * the block is then free, and once listed it belongs to this package.
     */
    #[Test]
    public function theRegistryLeavesTheBlockToThisPackage(): void
    {
        $registry = file(self::REGISTRY, FILE_IGNORE_NEW_LINES);
        self::assertIsArray($registry);
        $rows = 0;

        foreach ($registry as $line) {
            if (preg_match('/\A\| (\d{4})–(\d{4}) \| ([A-Z0-9—]+) \| ([^|]+?) \| (released|claimed|proposed|reserved) \|/u', $line, $row) !== 1) {
                continue;
            }

            ++$rows;
            $overlaps = (int) $row[1] <= self::LAST && (int) $row[2] >= self::FIRST;
            $ours = $row[4] === 'kariricode/transformer';

            if ($ours) {
                self::assertSame([(string) self::FIRST, (string) self::LAST, 'TRF'], [$row[1], $row[2], $row[3]], $line);
            }

            self::assertFalse($overlaps && !$ours, 'block taken by ' . $row[4]);
            self::assertFalse(!$ours && $row[3] === 'TRF', 'prefix TRF taken by ' . $row[4]);
        }

        self::assertGreaterThan(30, $rows, 'the registry table was read');
    }

    #[Test]
    public function codesFollowTheDocumentedLayout(): void
    {
        $ranges = [
            'rule definitions and the registry' => [3400, 3419],
            'bindings' => [3420, 3429],
            'integrations' => [3440, 3449],
        ];

        foreach (TransformerCode::cases() as $case) {
            $inRange = array_filter($ranges, static fn(array $range): bool => $case->value >= $range[0] && $case->value <= $range[1]);
            self::assertCount(1, $inRange, $case->name . ' sits in a documented range');
        }
    }
}
