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
ReflectionAttribute instantiation, aliases, direct require, and serialization
with targeted PHPT coverage.

## Gate 4: OPcache and Preload

Required:

- metadata persisted;
- behavior with OPcache matches no-OPcache;
- preload does not remove checks;
- cache invalidation correct;
- tests run with OPcache on/off.

Status: **Passed for OPcache/preload in the Docker debug build**. Gate 4
persists class and lexical caller metadata through OPcache shared memory and
file cache, persists attributed-declaration namespace metadata, keeps optimizer
class-constant/static-method shortcuts from bypassing restricted CEs, validates
OPcache CLI and file-cache replay, and validates preload metadata plus
dependency linking.

JIT validation after Gate 4 is **passed in the Docker debug build** for function
JIT, tracing JIT, namespace ranges, and inheritance/linking behavior. This closes
the deferred JIT correctness risk without making any performance claim.

## Gate 5: Performance

Required:

- public fast path measured;
- structure growth measured;
- benchmark reproducible;
- commands and configuration saved;
- results from multiple runs.

Status: **Passed for retained microbenchmarks**. Performance is measured. The
large public cache-hit regressions in static property access and callable
validation were traced to
repeated namespace checks on public cache hits and fixed by moving the checks to
cache population. The class-entry storage overhead was also eliminated:
`sizeof(zend_class_entry)` is back to 528 bytes in the release NTS container.
The remaining `public_instanceof` regression was traced to an AArch64 VM handler
layout issue and fixed by moving the const-class miss path to a cold helper. The
latest long paired run shows -0.49% without OPcache, -5.01% with
OPcache/no-JIT, +0.14% under function JIT, and +2.20% under tracing JIT. See
[11-performance-evidence.md](11-performance-evidence.md).

The 2026-06-23 trait-body metadata follow-up also measures the cold
compile/link path changed by preserving trait method lexical namespace metadata.
The final 5000-bundle `hyperfine` fixtures show +1.48% median for trait
aliases/adaptations and +0.54% median for trait precedence/`insteadof`, with no
sustained regression.

## Gate 6: RFC Readiness

Required:

- no unspecified implementation sections;
- known limitations listed;
- Future Scope separate;
- independent decisions are separate votes;
- tests correspond to normative text.

Status: **Passed for documentation reconciliation**, **not passed for voting**.
The draft, risk register, gates, roadmap, and acceptance checklist now use the
same status model: focused prototype gates are measured/passed, while broader
test-suite coverage, generated artifacts, publication packaging, and
post-discussion scope adjustments remain before a vote.

## Kill Criteria Applied

The C prototype is still marked incomplete because:

- generated artifacts and broader test coverage outside the focused prototype
  slices remain before voting.
