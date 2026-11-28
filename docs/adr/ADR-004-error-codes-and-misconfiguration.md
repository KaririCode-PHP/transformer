# ADR-004: Error Codes TRF 3400–3499 on the Exception Families; Misconfiguration Throws, Unconvertible Values Pass Untouched

## Status

Accepted — V3.0.0

## Date

2026-11-28

## Context

Spec V4.3.3 §8.4 identifies a failure by its kariricode/contract
exception family plus a numeric code that points to one component. Both
earlier lines threw `\InvalidArgumentException` (unknown or duplicate
alias) and declared a `TransformationException extends \RuntimeException`
that nothing threw, all without codes; a `hash` algorithm that does not
exist surfaced as PHP's own `ValueError`. The registry of record,
kariricode/exception's CODE-BLOCKS.md, had no block for the transformer.

A transformer also has to say what happens to a value a rule cannot
convert. The earlier rules mixed answers: return it unchanged (most),
convert it with loss (`json_decode` turned integers beyond PHP's range
into floats, `group_by` cast keys to strings and dropped records,
`csv_to_array` cut rows wider than the header), or fail with PHP's own
error (`csv_to_array` in 2.0.0 called `array_combine()` with rows of
another width, which throws a `ValueError` for input).

### Prior Art

- kariricode/exception 2.0 (ADR-002 there): the registry, the family
  bases, `[PREFIX_NNNN]` messages with neutralized control characters.
- kariricode/validator ADR-004 and kariricode/sanitizer ADR-004: VAL
  3200–3299 and SAN 3300–3399, the same line between misconfiguration
  and input.

## Decision

1. **The block.** TRF 3400–3499, the lowest free block of 100 codes
   after the sanitizer's (registry rule 3), laid out as 3400–3419 rule
   definitions and the registry, 3420–3429 container bindings,
   3440–3449 integrations. `TransformerCode` is the code table (backed,
   ARFA 1.3 E-003); the registry row is added with this release.
2. **Exceptions.** Every exception implements `TransformerException`
   (`$errorCode`), extends a kariricode/exception base, belongs to one
   family and is built only by named constructors:

   | Codes | Exception | Family |
   |---|---|---|
   | 3401–3406 | InvalidRuleException | Configuration |
   | 3420 | ConfigurationException | Configuration |
   | 3440 | UnsupportedInputException | Validation |

3. **Misconfiguration throws.** Mistakes in rules, rule maps,
   attributes and container bindings are the application's and throw
   (Configuration family), whatever the value being transformed — rules
   read their parameters first. That includes values PHP would reject
   only later: an algorithm `hash_algos()` does not list, a JSON depth
   outside 1–2147483647, JSON_PARTIAL_OUTPUT_ON_ERROR (which writes null
   for what it cannot encode).
4. **Values a rule cannot convert pass untouched.** A rule returns a
   value of a type it does not handle, or one it cannot convert without
   losing information, exactly as it received it; the record of changes
   then shows nothing for it, and the next step sees the original
   (ADR-008). Transforming never throws because of the input, except in
   the processor, which accepts arrays only (TRF_3440).
5. **Messages** name aliases, fields and parameters quoted with
   kariricode/exception's `quote()`, types instead of values, and never
   the data being transformed.

## Consequences

### Positive

- A log line `[TRF_3404] A rule on field "items" needs the parameter
  "field".` names the component, the mistake and where to fix it.
- Catching `TransformerException`, a family, or one class all work.
- Input never makes the transformer throw mid-request.

### Negative

- **Breaking:** code catching `\InvalidArgumentException` from the
  registry or PHP's `ValueError` from `hash` must change;
  `TransformationException` is gone.
- A value left untouched is easy to miss: check the record of changes,
  or validate the output, where a conversion must have happened.
- The registry row must reach a kariricode/exception release before the
  registry test can read it from an installed package; until then the
  test accepts a free block (QD-V433 open items).

## Alternatives Considered

- **SPL exceptions with codes:** no family, no prefix, no registry.
- **Throw for unconvertible input:** turns every malformed CSV upload
  into a 500; returning the value lets the application decide.
- **Convert with loss and record it:** the earlier `group_by` and
  `csv_to_array`, which dropped and cut data that nobody had asked to
  lose.

## References

- Spec V4.3.3 §8.4; ARFA 1.3 E-003.
- kariricode/exception CODE-BLOCKS.md (rules 1–5), ADR-002;
  kariricode/validator ADR-004; kariricode/sanitizer ADR-004.
- SPEC-001 §5.
