<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Core;

// ── Core: DataPath ───────────────────────────────────────────────────
// Reads and replaces a field in nested data by its dotted path.
// ─────────────────────────────────────────────────────────────────────

/**
 * Finds the value a field path names, and returns a copy of the data with
 * that value replaced: "email" is a key, "address.city" walks into nested
 * arrays. A key that contains dots itself is matched first, so flat form
 * data with dotted names works too. exists() tells a missing field from a
 * field set to null.
 *
 * Preconditions: with() replaces fields that exist; the engine skips the
 * others.
 *
 * Thread-safety: stateless.
 *
 * Side effects: none; with() returns a copy.
 *
 * Example:
 *
 *     DataPath::with(['address' => ['city' => ' Crato ']], 'address.city', 'Crato');
 *
 * References: SPEC-001 §4.
 */
final class DataPath
{
    /**
     * @param array<array-key, mixed> $data
     */
    public static function exists(array $data, string $path): bool
    {
        return self::walk($data, $path)[0];
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function value(array $data, string $path): mixed
    {
        return self::walk($data, $path)[1];
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array<array-key, mixed>
     */
    public static function with(array $data, string $path, mixed $value): array
    {
        if (array_key_exists($path, $data)) {
            return array_replace($data, [$path => $value]);
        }

        return self::put(data: $data, segments: explode('.', $path), value: $value);
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return array{bool, mixed}
     */
    private static function walk(array $data, string $path): array
    {
        if (array_key_exists($path, $data)) {
            return [true, $data[$path]];
        }

        $current = $data;

        foreach (explode('.', $path) as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return [false, null];
            }

            /** @var mixed $current */
            $current = $current[$segment];
        }

        return [true, $current];
    }

    /**
     * @param array<array-key, mixed> $data
     * @param non-empty-list<string>  $segments
     *
     * @return array<array-key, mixed>
     */
    private static function put(array $data, array $segments, mixed $value): array
    {
        $segment = $segments[0];
        $rest = array_slice($segments, 1);

        /** @var mixed $inner */
        $inner = $data[$segment] ?? null;

        return array_replace($data, [$segment => $rest === [] ? $value : self::put(data: is_array($inner) ? $inner : [], segments: $rest, value: $value)]);
    }
}
