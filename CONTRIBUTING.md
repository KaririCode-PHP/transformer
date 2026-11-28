# Contributing to KaririCode Transformer

Part of the KaririCode Framework. Contributions follow **ARFA 1.3 (Level 1)**
and the **KaririCode Specification V4.3.3**. A pull request is mergeable only
when `composer check` passes.

## Requirements

- PHP 8.4+ and Composer 2
- [KaririCode Devkit](https://github.com/KaririCode-PHP/devkit) (`kcode`)
  1.2.2, checked against its published SHA-256; pcov or Xdebug for mutation
  testing

```bash
git clone https://github.com/KaririCode-PHP/transformer.git
cd transformer
composer install     # the runtime dependencies, from composer.lock
kcode init           # the toolchain pinned in devkit.php, into .kcode/ (ADR-007)
composer check       # cs-check, phpstan, psalm, complexity, the three suites, mutation
composer security    # validate, audit of both locks, secret scan
```

## Quality Gates

| Gate | Command | Must report |
|---|---|---|
| Tests | `composer test` (`kcode test --testsuite=unit` for one suite) | OK, no warnings, notices, risky tests or deprecations |
| Conformance | `composer test-conformance` | OK |
| PHPStan, level max, strict and PHPUnit rules, cognitive complexity ≤ 15, `src` and `tests` | `composer phpstan` | `[OK] No errors` |
| Psalm, error level 1 | `composer psalm` | `No errors found!` |
| PER Coding Style | `composer cs-check` (`composer cs-fix` to apply) | 0 files to fix |
| Complexity (§13) | `composer complexity` | cyclomatic ≤ 10, nesting ≤ 3, parameters ≤ 4; `tools/complexity-baseline.json` may only shrink |
| Mutation testing | `composer mutation` | MSI ≥ 80 and covered MSI ≥ 80 |
| Security | `composer security` | valid manifest and lock, no advisories in either lock, no secrets |
| SBOM | `composer sbom` | CycloneDX 1.5 written to `sbom.cdx.json` |

A surviving mutant is either killed with a test or documented as equivalent
in the QD-V433 record.

## Code Standards

1. `declare(strict_types=1)` in every file; one class, interface, enum or
   trait per file.
2. Concrete classes are `final`; rules, contexts, results, events and
   settings are `final readonly`.
3. No `toArray()`/`fromArray()`/`jsonSerialize()`, no getters without logic
   (public readonly properties instead), no Manager/Helper/Util names.
4. No global or static mutable state: rules are shared across fields and
   requests, so they keep nothing between calls.
5. Rules return values they do not handle, or cannot convert without
   loss, unchanged — never a partial conversion; they read their
   parameters through the typed readers before the value, so a mistake
   in a rule map throws `InvalidRuleException` whatever the input
   (ADR-001, ADR-004, ADR-008).
6. Patterns anchor with `\A` and `\z`; comparisons are strict; dates are
   parsed through `Core\DateValue`, numbers through `Core\NumericValue`.
7. Every exception carries a code from block 3400–3499 and implements
   `Exception\TransformerException` (ADR-004); `src` throws no SPL exception.
8. Calls with three or more arguments name them (Spec V4.3.3 §30).
9. A new built-in rule gets an alias in `InMemoryRuleRegistry`, an entry
   in SPEC-002 and cases in its suite, including the misconfigurations.

### Documentation

- Every type has a docblock with its role, preconditions, thread safety,
  side effects, an example and references (Spec V4.3.3 §17).
- No tautological docs ("Gets the…", "Transforms the…", "Represents a…")
  and no `{@inheritDoc}`.

### Testing

- Every behaviour change comes with a test; every bug fix with a regression
  test that fails on the previous code.
- Rule suites extend `tests/Support/RuleTestCase.php`: a data provider of
  values and expected outputs, the names, the misconfigurations (checked
  with a value of another type too).
- Multibyte input for every text rule, both sides of every bound, values
  of other types for every rule, and the inputs a rule must return
  unchanged.
- Build exceptions inside a test, not in a data provider, so coverage and
  mutation testing attribute them to it.

## Architecture Decisions

Read `docs/adr/` before structural changes. New decisions get an ADR named
`ADR-NNN-title.md` with the sections Status, Date, Context (with Prior
Art), Decision, Consequences (Positive, Negative), Alternatives Considered
and References.

## Pull Request Process

1. Branch from `main`.
2. Write the failing test first.
3. Implement, then run `composer check`.
4. Add an entry under `[Unreleased]` in `CHANGELOG.md`.
5. Reference the ADRs and SPEC sections your change touches.
