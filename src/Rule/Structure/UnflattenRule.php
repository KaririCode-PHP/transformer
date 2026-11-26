<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Rule\Structure;

use KaririCode\Transformer\Contract\TransformationContext;
use KaririCode\Transformer\Contract\TransformationRule;

/** Unflattens dot-notation keys into nested arrays. */
/**
 * Converts a flat dot-notation array back to a nested structure.
 *
 * @package KaririCode\Transformer\Rule\Structure
 * @author  Walmir Silva <walmir.silva@kariricode.org>
 * @since   3.1.0 ARFA 1.3
 */
final readonly class UnflattenRule implements TransformationRule
{
    #[\Override]
    public function transform(mixed $value, TransformationContext $context): mixed
    {
        if (! \is_array($value)) {
            return $value;
        }
        $separator = (\is_string($_p = $context->getParameter('separator', '.')) ? $_p : '.');
        $sep = $separator !== '' ? $separator : '.';
        $result = [];

        foreach ($value as $key => $val) {
            $result = $this->assignPath($result, explode($sep, (string) $key), $val);
        }

        return $result;
    }

    /**
     * Writes $value at the nested $path, replacing any non-array node along the way.
     *
     * @param array<array-key, mixed> $target
     * @param list<string>            $path
     *
     * @return array<array-key, mixed>
     */
    private function assignPath(array $target, array $path, mixed $value): array
    {
        $segment = (string) array_shift($path);

        if ($path === []) {
            $target[$segment] = $value;

            return $target;
        }

        $child = $target[$segment] ?? null;
        $target[$segment] = $this->assignPath(\is_array($child) ? $child : [], $path, $value);

        return $target;
    }

    #[\Override]
    public function getName(): string
    {
        return 'structure.unflatten';
    }
}
