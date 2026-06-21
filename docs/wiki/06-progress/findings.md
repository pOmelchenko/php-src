# Findings

## Repository and Environment

- The current repository is php-src.
- Commit inspected: `0fff3ccce2f5f9e0695502a509fa8e8edf8f77d4`.
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
- `protected(namespace)` descendant access is straightforward except for global
  namespace and case normalization.
- `::class`, reflection construction, existence probes, and consistent
  accessibility remain the largest semantic questions.

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

## Feasibility

The feature appears feasible as a staged php-src implementation, but not as a
small parser-only patch if complete enforcement is required. A correct feature
crosses parser, compiler, class entries, class fetch, inheritance linking,
type resolution, reflection, OPcache, preload, JIT, and tests. The Docker
environment removes the local generator-tool blocker for the next parser and
metadata spike.
