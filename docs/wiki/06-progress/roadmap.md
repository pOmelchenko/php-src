# Roadmap

## Iteration 1: Documentation and Feasibility

Status: complete in this wiki.

Deliverables:

- repository/environment snapshot;
- prior-art review;
- language comparison;
- syntax comparison;
- formal visibility rules;
- access matrix;
- php-src implementation map;
- PHPT plan;
- RFC draft;
- decision log;
- open questions.

No C-code prototype was started.

## Iteration 2: Parser and Metadata Spike

Status: committed as `af9e9790f52`.

Entry requirements:

- build dependencies available (`re2c`, suitable Bison, configured build);
- decision on exact token strategy;
- minimal parser tests prepared.

Deliverables:

- syntax accepted for class/interface/trait/enum;
- syntax rejected for anonymous classes and invalid combinations;
- class-entry metadata stored;
- reflection/debug metadata visible;
- explicit warning that runtime enforcement is incomplete.

Completed so far:

- accepted syntax for named class/interface/trait/enum;
- rejected anonymous classes, duplicate namespace visibility prefixes, and
  explicit root syntax;
- class-entry flags and declaration namespace storage;
- ReflectionClass metadata methods;
- tokenizer token data;
- OPcache persistence size/store plumbing for the metadata string;
- Docker debug build, targeted PHPT run, and Docker ZTS debug build.

Remaining before closing Iteration 2:

- decide whether modifier order should stay prefix-only or accept both orders;
- add parser rejection for `internal` if needed;
- decide whether Reflection metadata methods are temporary prototype API or RFC
  surface.

## Iteration 3: Minimal Runtime Enforcement

Status: in progress in the working tree.

Entry requirements:

- metadata spike compiles and passes parser tests;
- central access-check API designed;
- global namespace and case handling decided for prototype.

Deliverables:

- `new ClassName()` static-name enforcement;
- dynamic `new $className` enforcement;
- core namespace allow/deny tests;
- allowed-then-denied cache-order test for construction.

Completed so far:

- central check for restricted class entries;
- `ZEND_NEW` and `ZEND_FETCH_CLASS` wiring;
- static and dynamic `new` tests;
- method caller namespace test;
- protected child/sibling and private child behavior;
- segment-prefix false-positive rejection;
- allowed-then-denied and denied-then-allowed cache-order test.

Remaining:

- replace current named-function/method caller namespace derivation with a
  complete lexical caller source;
- add top-level, closure, eval, trait, and method-specific lexical tests;
- add OPcache on/off checks for the covered construction paths.

## Iteration 4: Full Runtime Coverage

Deliverables:

- static access;
- callables;
- inheritance/linking;
- type positions;
- aliases;
- reflection;
- autoload order;
- eval, closures, and traits;
- serialization paths;
- complete PHPT coverage from the matrix.

## Iteration 5: OPcache/JIT/Preload

Deliverables:

- persistence of metadata;
- OPcache on/off parity;
- preload tests;
- JIT helper audit;
- runtime cache audit;
- performance measurements.

## Iteration 6: RFC Hardening

Status: started in Phase 2 documentation.

Deliverables:

- decide open questions;
- update RFC draft;
- collect implementation data;
- discuss with internals;
- adjust scope before any vote.

Completed in Phase 2 documentation:

- selected exact-only `private(namespace)` class-like declarations for RFC v1;
- excluded descendants, explicit root, `protected(namespace)`, `internal`,
  friend namespaces, and native consistent accessibility from RFC v1;
- created `docs/wiki/07-risk-closure` with risk register, normative semantics,
  operation coverage, runtime/cache map, Reflection/autoload/alias policy,
  adversarial review, RFC splitting, implementation gates, and acceptance
  checklist;
- rewrote the RFC draft to describe only the selected minimal scope;
- added DEC-020 through DEC-040 as superseding v1 decisions;
- replaced open questions with final dispositions and implementation gates;
- updated test and PHPT plans for exact-only v1.

Remaining before RFC discussion:

- align the C prototype with exact-only v1 syntax and semantics;
- complete class-entry enforcement coverage;
- validate OPcache/preload/JIT behavior;
- measure performance;
- rerun targeted and broader PHPT suites.

## Iteration 7: Exact-only v1 Implementation Alignment

Deliverables:

- reject class-level `protected(namespace)` in v1 parser/tests;
- normalize declaring and caller namespaces for checks;
- carry lexical namespace through top-level, closures, eval, traits, class
  linking, Reflection, internal functions, and unserialize;
- enforce all operations in the v1 operation matrix;
- preserve checks on cache hits and aliases;
- run OPcache/preload tests;
- record benchmark evidence.
