# Findings

## Repository and Environment

- The current repository is php-src.
- Initial commit inspected: `0fff3ccce2f5f9e0695502a509fa8e8edf8f77d4`.
- Phase 2 documentation commit base inspected:
  `1c1d3a699c624030ca0582daedfebcba723c8ddc`.
- Branch inspected: `packages`.
- PHP version header reports `8.6.0-dev`.
- Local build environment is not ready for parser or PHPT work:
  `configure`, `Makefile`, and `sapi/cli/php` are absent; `re2c` is missing;
  Bison is GNU Bison 2.3.
- A Docker development environment was added after the first documentation
  iteration. The built image provides Bison 3.8.2, re2c 3.0, Autoconf 2.71, and
  a mounted checkout at `/workspaces/php-src`.

## Prior Art

- The active PHP member/property RFC is highly relevant for lexical namespace,
  closures, callables, traits, op_array metadata, runtime enforcement, and
  OPcache persistence.
- The old class/interface/trait namespace visibility RFC is relevant but old,
  draft-only, and uses syntax/semantics that should not be copied directly.
- No current official PHP RFC page titled exactly "Private classes and
  functions" was found in the RFC index on 2026-06-21.
- D and Rust are the strongest explicit-root prior art.
- C#, Kotlin, and Swift show that `internal` belongs to a real module,
  assembly, or package boundary.

## Semantics

- The most practical first boundary is name use, not runtime membrane.
- Lexical caller namespace is essential.
- `private(namespace)` exact match is straightforward.
- `protected(namespace)` is now the selected class-level descendant access
  spelling, with an explicit terminology caveat because the active member RFC
  uses the same spelling differently in Future Scope.
- Explicit root is useful but deferred.
- `::class`, Reflection construction, existence probes, aliases, autoload side
  effects, global namespace, and public API exposure now have explicit v1
  dispositions.
- Namespace comparisons for class-like visibility must normalize case according
  to class-like lookup semantics.
- Trait body operations should use the trait declaration namespace; current
  php-src trait scope fixup means implementation needs extra metadata.

## Implementation

Hotspots:

- `Zend/zend_language_scanner.l`
- `Zend/zend_language_parser.y`
- `Zend/zend_compile.c`
- `Zend/zend_compile.h`
- `Zend/zend.h`
- `Zend/zend_execute_API.c`
- `Zend/zend_vm_def.h`
- `Zend/zend_inheritance.c`
- `Zend/zend_API.c`
- `Zend/zend_object_handlers.c`
- `ext/reflection/php_reflection.c`
- `ext/opcache/zend_persist.c`
- `ext/opcache/zend_persist_calc.c`
- `ext/opcache/ZendAccelerator.c`
- `ext/opcache/jit/zend_jit.c`
- `ext/opcache/jit/zend_jit_helpers.c`
- `ext/spl/php_spl.c`
- `ext/tokenizer`

Most important design constraint:

- Class entry cache hits must not skip access checks for later callers from
  disallowed namespaces.

Phase B prototype findings, updated after private/protected alignment:

- `private(namespace)` and `protected(namespace)` can be tokenized as dedicated
  scanner tokens, matching the `private(set)`/`protected(set)` precedent.
- A conflict-free grammar spike is simplest when namespace visibility is a
  declaration prefix before normal class modifiers:
  `private(namespace) final class A {}` and
  `protected(namespace) abstract class B {}`.
- Allowing both `final private(namespace) class A {}` and
  `private(namespace) final class A {}` is possible future work, but it
  increases grammar and duplicate-modifier handling.
- The class declaration AST `attr` field is a better Phase B carrier than
  temporary `ce_flags` bits, because ordinary class flags have very little free
  space and bit 30 is already used by `ZEND_ACC_USE_GUARDS`.
- The prototype maps private/protected namespace visibility bits from
  `zend_ast_decl->attr` into `ce_flags2` during `zend_compile_class_decl()`.
- Metadata belongs on `zend_class_entry`, so `class_alias()` cannot be allowed
  to widen visibility later.
- Reflection metadata is useful for tests, but it is not an enforcement
  mechanism.

## Feasibility

The feature appears feasible as a staged php-src implementation, but not as a
small parser-only patch if complete enforcement is required. A correct feature
crosses parser, compiler, class entries, class fetch, inheritance linking,
type resolution, reflection, OPcache, preload, JIT, and tests. The Docker
environment removed the local generator-tool blocker and the Phase B
parser/metadata spike builds and passes targeted tests. Phase C now has a first
runtime enforcement slice for `new`, but complete enforcement remains the hard
part.

## Phase C Findings

- Checking after CE cache lookup is necessary and feasible for `ZEND_NEW` and
  `ZEND_FETCH_CLASS`; the first cache-order tests pass.
- The fast path for unrestricted classes keeps the new check cheap for normal
  code.
- The current comparison normalizes declaration and caller namespaces according
  to class-like lookup case behavior.
- `protected(namespace)` is now a parser rejection test, not an accepted
  descendant mode.
- Deriving caller namespace from named function/method metadata is enough for
  the first construction tests, but it is not a complete lexical namespace
  model.
- A final implementation still needs per-operation or op_array-level lexical
  namespace metadata that handles top-level namespace blocks, closures, arrow
  functions, eval, and `Closure::bind()`.
- No OPcache, preload, or JIT behavior has been validated for enforcement yet.

## Phase 2 Risk-Closure Findings

- The selected first RFC is now class-like `private(namespace)` exact access
  plus `protected(namespace)` namespace-subtree access.
- The risk register contains 9 RESOLVED, 5 MITIGATED, 1 DEFERRED,
  2 ACCEPTED, and 0 BLOCKED risks.
- Performance evidence is NOT MEASURED.
- The current C prototype accepts class-level `private(namespace)` and
  `protected(namespace)`, normalizes namespace comparison metadata, and covers
  only a small runtime slice.
- Gate 1 is reopened at the documentation level for the protected terminology
  caveat. Gate 2 passes for the parser/metadata slice. Gates 3 through 5 are
  not passed for the selected v1 implementation.
