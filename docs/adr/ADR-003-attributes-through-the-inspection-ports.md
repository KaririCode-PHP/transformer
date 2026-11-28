# ADR-003: Attribute Transformation through the kariricode/contract Inspection Ports

## Status

Accepted — V3.0.0

## Date

2026-11-28

## Context

`#[Transform]` on a DTO's properties declares the rules next to the
data, and applying them takes reflection twice: once to read the
attributes and values, once to write the results. The earlier lines did
both by mutating the object in place:

- The September 2026 tarball reflected by itself and called
  `ReflectionProperty::setValue()` on the original object.
- The March 2026 main line (2.0.0) went through
  kariricode/property-inspector 2.0's handler, whose `applyChanges()`
  also wrote into the original.

Both fail on the DTOs the ecosystem recommends: writing an initialized
readonly property throws `Error: Cannot modify readonly property`. And
both changed an object that other code may still hold.

kariricode/property-inspector 3.0 moved the inspection contracts into
kariricode/contract 2.12 (`PropertyInspector`, `PropertyAttributeHandler`,
`PropertyChangeApplier`) and added `ReflectionChangeApplier`, which
"writes processed values back by copy" — the way its own documentation
says a sanitizer or a transformer updates a readonly DTO.

### Prior Art

- kariricode/property-inspector ADR-006 (change applier), ADR-008
  (object-typed inspection API), ADR-009 (ecosystem contract alignment).
- kariricode/contract ADR-007 (Inspection namespace).
- kariricode/validator ADR-003 (the same ports, read-only) and
  kariricode/sanitizer ADR-003 (the same copy).

## Decision

1. `AttributeTransformer` takes a `TransformerEngine`, a contract
   `PropertyInspector` and a contract `PropertyChangeApplier`. It
   inspects the object for `#[Transform]`, transforms the collected values
   with the engine and returns a `TransformedObject` holding the object
   and the `TransformationResult`.
2. Only the properties whose value changed are handed to the applier,
   which returns a copy; when nothing changed, the `TransformedObject`
   holds the original instance and the applier is not called.
3. `Core\AttributeRules` implements `PropertyAttributeHandler`: repeated
   `#[Transform]` attributes on one property join their rules in order;
   other attributes are ignored.
4. kariricode/property-inspector is a `suggest`: array transformation
   needs no reflection, and any implementation of the two interfaces
   works. The README and the provider point to
   `ReflectionPropertyInspector` and `ReflectionChangeApplier`.
5. The `object` parameters of the package are the contract's and the
   copy it returns: `AttributeRules::handleAttribute($attribute)`,
   `AttributeTransformer::transform($object)` and
   `TransformedObject::__construct($object)`, pinned by the conformance
   suite as accepted deviations, as property-inspector's ADR-008 does.
6. `#[Transform]` takes positional definitions only; named arguments
   throw `InvalidRuleException` (TRF_3406), because PHP would collect
   them under string keys the engine cannot read.

## Consequences

### Positive

- Readonly DTOs work, and the caller's object is never modified.
- One reflection implementation, with its cache, for the processing
  family; the transformer depends on interfaces only.
- `$transformed->object === $original` tells a caller cheaply that nothing
  changed.

### Negative

- The copy is built without running the constructor, so invariants the
  constructor enforces are not re-checked: validate the copy, not the
  original (property-inspector says the same).
- Attribute transformation needs a second package, or an implementation
  of two short interfaces.
- `transform()` returns a `TransformedObject` instead of the result alone,
  a breaking change for 2.x callers.

## Alternatives Considered

- **Keep writing in place:** no copy, and an `Error` for every readonly
  DTO.
- **Return only the copy:** loses the record of changes; returning only
  the result (2.x) loses the copy.
- **Require kariricode/property-inspector:** simpler installation, and
  a hard dependency array transformation does not need.

## References

- kariricode/property-inspector ADR-006, ADR-008, ADR-009;
  kariricode/contract ADR-007.
- Spec V4.3.3 §30 (object parameters), §31 (dependencies).
- SPEC-001 §7.
