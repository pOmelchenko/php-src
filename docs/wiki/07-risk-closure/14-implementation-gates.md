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

Status: **Not passed**. Phase C covers only `new`/`ZEND_FETCH_CLASS` basics.

## Gate 4: OPcache and Preload

Required:

- metadata persisted;
- behavior with OPcache matches no-OPcache;
- preload does not remove checks;
- cache invalidation correct;
- tests run with OPcache on/off.

Status: **Not passed**. OPcache persistence was touched in Phase B, but v1
semantic behavior with OPcache/preload has not been verified.

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

The C prototype is marked incomplete because:

- it does not enforce every operation promised by the symbol-visibility model;
- static access, inheritance, types, Reflection, aliases, OPcache/preload, and
  `instanceof`/`catch` are not fully covered;
- performance is not measured.
