<?php

declare(strict_types=1);

namespace KaririCode\Transformer\Tests\Support;

use KaririCode\Transformer\Attribute\Transform;
use KaririCode\Transformer\Rule\String\MaskRule;

// ── Support: Customer ────────────────────────────────────────────────
// A readonly DTO with #[Transform], as an application writes one.
// ─────────────────────────────────────────────────────────────────────

/** A customer record: aliases, chained attributes, a rule instance with parameters, an untouched property. */
final readonly class Customer
{
    /**
     * @param string|array<array-key, mixed> $preferences
     */
    public function __construct(
        #[Transform('cpf_to_digits')]
        public string $cpf,
        #[Transform('phone_format')]
        public string $phone,
        #[Transform([new MaskRule(), ['keep_start' => 0, 'keep_end' => 4]])]
        public string $card = '',
        #[Transform('json_decode')]
        #[Transform('flatten')]
        public string|array $preferences = '{}',
        public string $notTransformed = '123.456.789-09',
    ) {}
}
