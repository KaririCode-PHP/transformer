<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Tests\Unit\Result;

use KaririCode\Transformer\Result\FieldTransformation;
use KaririCode\Transformer\Result\TransformationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

// ── Unit: TransformationResultTest ───────────────────────────────────
// The result: values by path, what changed, the recorded changes.
// ─────────────────────────────────────────────────────────────────────

#[CoversClass(TransformationResult::class)]
#[CoversClass(FieldTransformation::class)]
final class TransformationResultTest extends TestCase
{
    #[Test]
    public function anUnchangedResultReportsNoChange(): void
    {
        $result = new TransformationResult(original: ['a' => 1], transformed: ['a' => 1]);

        self::assertFalse($result->wasTransformed());
        self::assertFalse($result->isFieldTransformed('a'));
        self::assertFalse($result->isFieldTransformed('missing'));
        self::assertSame([], $result->transformations);
        self::assertSame([], $result->transformedFields());
        self::assertSame([], $result->transformationsFor('a'));
        self::assertSame(0, $result->transformationCount());
        self::assertSame(1, $result->get('a'));
        self::assertNull($result->get('missing'));
    }

    #[Test]
    public function changesAreQueriedByField(): void
    {
        $snake = new FieldTransformation(field: 'user.column', ruleName: 'string.snake_case', before: 'First Name', after: 'first_name');
        $kebab = new FieldTransformation(field: 'user.column', ruleName: 'string.kebab_case', before: 'first_name', after: 'first-name');
        $words = new FieldTransformation(field: 'total', ruleName: 'numeric.number_to_words', before: 42, after: 'forty-two');
        $result = new TransformationResult(
            original: ['user' => ['column' => 'First Name', 'name' => 'Ana'], 'total' => 42],
            transformed: ['user' => ['column' => 'first-name', 'name' => 'Ana'], 'total' => 'forty-two'],
            transformations: [$snake, $kebab, $words],
        );

        self::assertTrue($result->wasTransformed());
        self::assertTrue($result->isFieldTransformed('user.column'));
        self::assertTrue($result->isFieldTransformed('total'));
        self::assertFalse($result->isFieldTransformed('user.name'));
        self::assertSame('first-name', $result->get('user.column'));
        self::assertSame(['user.column', 'total'], $result->transformedFields());
        self::assertSame([$snake, $kebab], $result->transformationsFor('user.column'));
        self::assertSame([$words], $result->transformationsFor('total'));
        self::assertSame([], $result->transformationsFor('user.name'));
        self::assertSame(3, $result->transformationCount());
        self::assertSame(['user.column', 'string.snake_case', 'First Name', 'first_name'], [$snake->field, $snake->ruleName, $snake->before, $snake->after]);
    }

    #[Test]
    public function aValueTurnedIntoAnotherTypeCountsAsAChange(): void
    {
        $result = new TransformationResult(original: ['age' => '42', 'flag' => 0], transformed: ['age' => 42, 'flag' => false]);

        self::assertTrue($result->isFieldTransformed('age'));
        self::assertTrue($result->isFieldTransformed('flag'));
        self::assertTrue($result->wasTransformed());
    }
}
