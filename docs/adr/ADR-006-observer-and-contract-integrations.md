# ADR-006: An Observer for Events; the Contract Processor, Transformer and ServiceProvider

## Status

Accepted — V3.0.0

## Date

2026-11-28

## Context

ARFA 1.3 P5 asks components to be observable, and the ecosystem wires
components through kariricode/contract ports. The earlier lines did
neither consistently:

- The main line (2.0.0) declared `TransformationStartedEvent` and
  `TransformationCompletedEvent` and never emitted them; the September
  2026 tarball emitted them to an optional closure.
- `ProcessorBridge` implemented no contract and returned
  `['data' => …, 'result' => …]` instead of the data a next step could
  take.
- `TransformerServiceProvider` was a factory (`createRegistry()`,
  `createEngine()`, `createAttributeTransformer()`) that implemented no
  container contract.

### Prior Art

- kariricode/validator ADR-006 and kariricode/sanitizer ADR-006: the
  same observer, contract `Processor`s and a contract `ServiceProvider`.
- kariricode/processor-pipeline: steps are contract `Processor`s.
- PSR-14: an event dispatcher is a callable away from a closure.

## Decision

1. **Observer.** `TransformerEngine` takes an optional `Closure` and calls
   it with a `TransformationStartedEvent` (field paths, Unix time) and a
   `TransformationCompletedEvent` (result, duration in milliseconds from
   the monotonic clock) per call. The started event carries names only;
   the completed event carries the result, values included, because an
   audit of changes is its purpose — an observer that logs it decides
   what to redact. A PSR-14 dispatcher plugs in as
   `static fn (object $event) => $dispatcher->dispatch($event)`.
2. **Processor and Transformer.** `Integration\TransformationProcessor`
   implements kariricode/contract's `Processor` and `Transformer` for a
   fixed rule map: `process()` and `transform()` return the transformed
   array. Input that is not an array throws `UnsupportedInputException`
   (TRF_3440); a caller that needs the record of changes uses the
   engine.
3. **ServiceProvider.** `TransformerServiceProvider` implements
   kariricode/contract's `ServiceProvider` and binds, as singletons, the
   registry with the built-in rules, the engine (with the configuration
   and observer the provider was given) and the `AttributeTransformer`,
   which resolves the container's `PropertyInspector` and
   `PropertyChangeApplier`. A binding of the wrong type throws
   `ConfigurationException` (TRF_3420).

## Consequences

### Positive

- The transformer joins a kariricode/processor-pipeline as one step and
  a framework container as one provider, with nothing but
  kariricode/contract.
- Metrics and audits need no dependency in this package.

### Negative

- **Breaking:** `ProcessorBridge` and the factory methods are gone;
  `TransformationCompletedEvent` no longer has `$timestamp` (the started
  event has one, and the duration gives the end).
- The observer runs synchronously; a slow observer slows every call.
- An observer that logs `TransformationCompletedEvent::$result` logs the
  input: redact before writing it anywhere.

## Alternatives Considered

- **Require psr/event-dispatcher:** one more dependency for what a
  closure already does.
- **Return the result from the processor:** the contract `Processor`
  passes values between steps; a result object would force every next
  step to unwrap it.
- **Keep the factory provider:** convenient without a container, and
  `new TransformerEngine()` already is that convenience.

## References

- ARFA 1.3 P5; kariricode/contract Processor, Transformer,
  DI\ServiceProvider.
- PSR-14, Event Dispatcher.
- SPEC-001 §9.
