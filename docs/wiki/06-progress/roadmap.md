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

Status: complete for the original `new`/class-fetch slice; superseded by
Iteration 7 for the broader v1 alignment work.

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

Remaining Iteration 3 items: none. Complete lexical caller coverage,
OPcache/preload/JIT validation, and broader operation coverage are tracked in
Iterations 4, 5, and 7.

## Iteration 4: Full Runtime Coverage

Status: passed for the focused Gate 3 prototype slice; broader suite evidence
and explicitly deferred attribute-instantiation timing remain before voting.

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

Status: passed for focused Gate 4 correctness and Gate 5 performance evidence;
generated artifacts and broader suite evidence remain before voting.

Deliverables:

- persistence of metadata;
- OPcache on/off parity;
- preload tests;
- JIT helper audit;
- runtime cache audit;
- performance measurements.

## Iteration 6: RFC Hardening

Status: documentation reconciled for an implementation-backed discussion
package; not voting-ready.

Deliverables:

- decide open questions;
- update RFC draft;
- collect implementation data;
- discuss with internals;
- adjust scope before any vote.

Completed in Phase 2 documentation:

- selected `private(namespace)` exact access and `protected(namespace)`
  namespace-subtree access for RFC v1;
- excluded explicit root, `internal`,
  friend namespaces, and native consistent accessibility from RFC v1;
- created `docs/wiki/07-risk-closure` with risk register, normative semantics,
  operation coverage, runtime/cache map, Reflection/autoload/alias policy,
  adversarial review, RFC splitting, implementation gates, and acceptance
  checklist;
- rewrote the RFC draft to describe the selected private/protected scope;
- added DEC-020 through DEC-040 as superseding v1 decisions;
- replaced open questions with final dispositions and implementation gates;
- updated test and PHPT plans for the revised v1 model;
- reconciled the RFC draft, risk register, implementation gates, roadmap, and
  acceptance checklist with the current trait-body metadata, OPcache/preload,
  file-cache, JIT, and performance evidence.

Remaining before public RFC discussion:

- publish the branch;
- prepare a concise discussion package with scope, semantics, implementation
  evidence, performance evidence, accepted risks, and future scope.

Remaining before voting:

- rerun targeted and broader PHPT suites;
- finalize generated artifacts and release-clean build artifacts;
- adjust scope after internals discussion if needed.

## Iteration 7: Private/protected v1 Implementation Alignment

Deliverables:

- accept class-level `protected(namespace)` in v1 parser/tests; **done for the
  current parser slice**;
- normalize declaring and caller namespaces for checks; **done for the current
  focused slices**;
- carry lexical namespace through top-level, closures, eval, traits, class
  linking, Reflection, internal functions, and unserialize; **done for trait
  body operations and the existing focused slices**;
- enforce all operations in the v1 operation matrix; **done for the Gate 3
  focused slice, excluding explicitly deferred attribute-instantiation timing**;
- preserve checks on cache hits and aliases; **done for focused VM, callable,
  OPcache, alias, and trait-body tests**;
- run OPcache/preload tests; **done for OPcache CLI, file cache, preload,
  preload linking, JIT, and trait-body metadata focused coverage**;
- record benchmark evidence; **done for the retained public hot-path gate and
  trait-body metadata compile/link follow-up**.
