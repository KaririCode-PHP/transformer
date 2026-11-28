<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Exception;

use KaririCode\Exception\ConfigurationException as ConfigurationBase;

// ── Exception: ConfigurationException ────────────────────────────────
// A container binding has the wrong type (code 3420).
// ─────────────────────────────────────────────────────────────────────

/**
 * Reports a container that resolves one of the provider's services to
 * something that is not an instance of it, such as a PropertyInspector
 * binding that returns an array.
 *
 * Preconditions: built only through the named constructor.
 *
 * Thread-safety: an exception, created per failure.
 *
 * Side effects: none.
 *
 * Example:
 *
 *     throw ConfigurationException::invalidBinding(PropertyInspector::class, 'array');
 *
 * References: ADR-004; ADR-006.
 */
final class ConfigurationException extends ConfigurationBase implements TransformerException
{
    public static function invalidBinding(string $id, string $type): self
    {
        return new self(TransformerCode::InvalidBinding, 'The container resolves ' . self::quote($id) . " to {$type}, which is not an instance of it.");
    }
}
