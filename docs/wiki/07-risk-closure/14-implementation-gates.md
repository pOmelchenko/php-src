# Implementation Gates

## Gate 1: Semantic Closure

Required:

- selected syntax;
- selected scope;
- one access invariant;
- lexical caller defined;
- traits defined;
- Reflection and aliases defined;
- global namespace defined;
- error timing defined.

Status: **Passed for documentation**, not for implementation.

## Gate 2: Parser and Metadata

Required:

- syntax parses;
- invalid combinations rejected;
- flags preserved;
- declaration namespace stored normalized and diagnostic spelling preserved if needed;
- Reflection/debug output exposes metadata;
- PHPT parser tests pass.

Status: **Passed for the private/protected parser/metadata prototype slice**.
The working tree parses `private(namespace)` and `protected(namespace)`, stores
normalized declaration namespace metadata, exposes Reflection metadata, and
passes targeted parser/tokenizer PHPTs.

## Gate 3: Enforcement Coverage

Required:

- all operations in [05](05-operation-coverage.md) classified;
- no known class-fetch bypass;
- allowed-to-denied cache tests pass;
- dynamic class tests pass;
- aliases do not remove visibility;
- direct `require` does not bypass checks.

Status: **Passed in the Docker debug build**. Gate 3 covers VM class-name
semantic operations, linking, type positions, callables, Reflection allocation,
aliases, direct require, and serialization with targeted PHPT coverage.

## Gate 4: OPcache and Preload

Required:

- metadata persisted;
- behavior with OPcache matches no-OPcache;
- preload does not remove checks;
- cache invalidation correct;
- tests run with OPcache on/off.

Status: **Passed for OPcache/preload in the Docker debug build**. Gate 4
persists class and lexical caller metadata through OPcache shared memory and
file cache, keeps optimizer class-constant/static-method shortcuts from
bypassing restricted CEs, validates OPcache CLI and file-cache replay, and
validates preload metadata plus dependency linking. JIT remains outside Gate 4.

## Gate 5: Performance

Required:

- public fast path measured;
- structure growth measured;
- benchmark reproducible;
- commands and configuration saved;
- results from multiple runs.

Status: **Not passed**. Performance is NOT MEASURED.

## Gate 6: RFC Readiness

Required:

- no unspecified implementation sections;
- known limitations listed;
- Future Scope separate;
- independent decisions are separate votes;
- tests correspond to normative text.

Status: **Partially passed for documentation**, **not passed for voting**.

## Kill Criteria Applied

The C prototype is still marked incomplete because:

- Gate 5 performance is not measured;
- JIT behavior remains deferred to a separate validation/fix gate;
- remaining RFC-readiness work must reconcile documentation, generated
  artifacts, and broader test coverage outside the focused prototype slices.
