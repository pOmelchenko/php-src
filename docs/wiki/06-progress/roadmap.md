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

Deliverables:

- decide open questions;
- update RFC draft;
- collect implementation data;
- discuss with internals;
- adjust scope before any vote.
