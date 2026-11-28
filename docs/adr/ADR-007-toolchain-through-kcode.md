# ADR-007: Quality Gates through KaririCode Devkit, with the Root Configs

## Status

Accepted — V3.0.0

## Date

2026-11-28

## Context

The September 2026 tarball had PHPUnit, PHPStan and PHP-CS-Fixer in
`require-dev`, no lock, no Psalm, no mutation testing, no security
checks and no CI. Measured with this release's configurations, its
`src` had 56 PHPStan errors at level max with the strict rules and 136
Psalm errors at level 1 (71 missing `#[\Override]`, 65 type errors).
The main line (2.0.0) ran PHP-CS-Fixer, PHPStan, Psalm and PHPUnit in
CI through the latest kcode release, unpinned, with actions pinned by
tag only.

KaririCode Specification V4.3.3 §29.1 names eight gates: the tests, the
conformance suite, PHPStan at level max, Psalm at error level 1, PER
Coding Style, the §13 complexity budgets, mutation testing with MSI ≥
80, and security. The ecosystem runs them through KaririCode Devkit with
the configs a component commits (`'root_configs' => true`).

### Prior Art

- KaririCode Devkit 1.2.2 (`kcode init`, `kcode test`, `kcode run`).
- kariricode/validator ADR-007, kariricode/sanitizer ADR-007: the same
  move, with the shared `tools/complexity.php`, `tools/sbom.php` and
  secret scan.

## Decision

| Item | Before | Now |
|---|---|---|
| Runtime | PHP only (tarball); kariricode/property-inspector ^2.0 (2.0.0) | PHP ^8.4, `kariricode/contract` ^2.12 (ADR-003, ADR-006), `kariricode/exception` ^2.0.2 (ADR-004); `kariricode/property-inspector` suggested |
| Tools | `require-dev` (tarball); kcode "latest" (2.0.0) | `devkit.php`: PHPUnit 11.5.56, PHPStan 2.3.7 with phpstan-strict-rules 2.1.2, phpstan-phpunit 2.1.4 and tomasvotruba/cognitive-complexity 1.2.0, Psalm 6.19.1, PHP-CS-Fixer 3.95.27, Infection 0.35.5 |
| `composer.lock` | none (tarball) | committed, resolved from Packagist with a clean Composer home; `export-ignore` |
| Suites | unit, integration, conformance (V4.0) | unit, integration and conformance (V4.3.3); warnings, notices, deprecations and risky tests fail |
| Static analysis | PHPStan, not at max | PHPStan level max on `src` and `tests` with the strict, PHPUnit and cognitive rules; Psalm level 1 on `src`, no baseline, `#[\Override]` required |
| Style, complexity | PHP-CS-Fixer | PER-CS 3.0 (E-046); `tools/complexity.php` with an empty baseline |
| Mutation, security | none | `composer mutation` (MSI ≥ 80, covered MSI ≥ 80); `composer security`: validation, audit of both locks, secret scan |
| CI | none (tarball); actions by tag (2.0.0) | the gates on PHP 8.4 with pcov, kcode pinned by version and SHA-256, actions by commit SHA; the suites on PHP 8.5 with the lowest versions the constraints allow |
| `extra` | none | `kariricode`: `arfa` 1.3 level 1, `asvs` 5.0 level 2, `spec` 4.3.3, `error-code-range` TRF 3400–3499; `branch-alias` `3.x-dev` |

Psalm 6.19 does not model PHP 8.4 interface properties
(`TransformationContext::$field`, `$data`, `$parameters`,
`TransformerException::$errorCode`); `psalm.xml` suppresses
`NoInterfaceProperties`, as kariricode/validator and kariricode/sanitizer
do.

## Consequences

### Positive

- The package installs and runs the same gates, commands and versions as
  the other components; both locks are audited.

### Negative

- Contributors need kcode 1.2.2; plain `composer install` gives the
  runtime only.
- A toolchain upgrade is a change to `devkit.php` and the toolchain
  lock, verified by running every gate.

## Alternatives Considered

- **Tools in `require-dev`:** the toolchain's dependencies would mix
  with the package's in one lock (Spec V4.3.3 §32 keeps them apart).
- **Psalm baseline:** hides errors instead of fixing them; the code
  passes without one.

## References

- KaririCode Specification V4.3.3 §13, §19.4, §25, §29.1, §32, E-044,
  E-046.
- KaririCode Devkit 1.2.2.
