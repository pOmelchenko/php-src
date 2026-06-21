# Prototype Plan

## Phase A: Research Base

Status: done for this documentation iteration.

Tasks:

- create documentation tree;
- record environment and repository state;
- survey prior PHP RFCs and other languages;
- choose a syntax candidate for prototype;
- define formal visibility rules;
- build access and PHPT matrices;
- map php-src implementation hotspots;
- avoid semantic C-code changes.

## Phase B: Parser and Metadata Spike

Goal: accept syntax and store metadata, without claiming complete enforcement.

Status: committed as `af9e9790f52`.

Tasks:

- recognize `private(namespace)` and `protected(namespace)`;
- reject explicit root syntax in this phase;
- reject namespace visibility on anonymous classes;
- add AST/class flags for class-like namespace visibility;
- store declaration namespace and effective root on `zend_class_entry`;
- support class, interface, trait, and enum declarations;
- expose metadata through temporary debug output or reflection methods;
- add parser accepted/rejected PHPTs;
- add reflection/debug metadata PHPTs.

Known incomplete paths in Phase B:

- runtime access checks;
- dynamic class strings;
- inheritance enforcement;
- reflection construction enforcement;
- OPcache/JIT/preload behavior.

Phase B must be documented as an incomplete experimental spike.

Current implementation notes:

- The prototype uses dedicated scanner tokens `T_PRIVATE_NAMESPACE` and
  `T_PROTECTED_NAMESPACE`.
- The accepted order is currently prefix-only:
  `private(namespace) final class A {}` and
  `protected(namespace) abstract class B {}`.
- `final private(namespace) class A {}` and
  `abstract protected(namespace) class B {}` are not accepted by this spike.
- Parser-only namespace visibility bits live in `zend_ast_decl->attr` and are
  transferred into `ce_flags2` in `zend_compile_class_decl()`.
- The declaration namespace is stored on `zend_class_entry` as an interned
  string for restricted declarations; the global namespace uses an empty string.
- ReflectionClass exposes the metadata through prototype methods.
- OPcache persistence calculation/store paths include the new class-entry
  string, but runtime OPcache behavior has not been tested yet.

## Phase C: Central Access Check

Goal: one authoritative class-like visibility check.

Status: in progress as an incomplete experimental spike.

The central function should receive:

- target `zend_class_entry`;
- lexical caller namespace;
- operation/context kind;
- diagnostic detail.

Tasks:

- implement exact private and descendant protected comparisons;
- add fast path for unrestricted classes;
- define global namespace behavior;
- define case-normalization behavior;
- add diagnostics;
- wire into a minimal runtime path such as `new ClassName()`;
- add PHPTs for allowed/denied same/child/sibling/prefix cases.

Completed so far:

- central fast-path check for unrestricted classes;
- exact private namespace comparison;
- protected descendant comparison by full namespace segment;
- stable runtime `Error` message without absolute paths;
- checks wired into `ZEND_NEW` and `ZEND_FETCH_CLASS`;
- targeted tests for static `new`, dynamic `new $class`, method caller
  namespace, segment-prefix false positives, and cache order.

Known incomplete paths:

- caller namespace is derived from named function/method metadata, not from a
  full lexical per-operation source;
- top-level namespace blocks are not correctly represented yet;
- closures, arrow functions, `Closure::bind()`, eval, and trait composition
  require a stronger caller namespace design;
- inheritance, static access, types, aliases, Reflection construction, OPcache,
  preload, and JIT are not complete.

## Phase D: Complete Access Coverage

Goal: cover the full access matrix.

Tasks:

- class fetch and runtime cache hits;
- dynamic `new`;
- static method/property/constant access;
- callables and internal functions;
- `extends`, `implements`, interface extends, trait use;
- type positions;
- `instanceof` and `catch`;
- aliases;
- reflection;
- autoload order;
- direct require;
- serialize/unserialize;
- eval and closures;
- allowed-then-denied and denied-then-allowed cache order.

Do not call the feature complete until this phase is covered.

## Phase E: OPcache, Preload, and JIT

Goal: same behavior in optimized paths.

Tasks:

- persist CE metadata;
- persist op_array caller namespace metadata if added;
- update persistence size calculation;
- test OPcache on/off;
- test preload;
- audit inheritance cache;
- audit JIT known-class helpers;
- verify invalidation and stale cache behavior.

## Phase F: Explicit Root

Goal: evaluate and implement only after base semantics stabilize.

Tasks:

- parse `protected(namespace: \Root)`;
- validate root is declaration namespace or ancestor;
- reject unrelated roots;
- decide global-root behavior;
- update reflection metadata;
- update OPcache persistence;
- add root-specific access and syntax tests;
- document multiple roots and friend namespaces as future scope unless a later
  RFC expands them.

## Commands Used for Phase B and Phase C

Docker debug build and targeted tests:

```sh
docker compose -f docker/dev/compose.yml run --rm php-src-dev bash -lc '
  ./buildconf --force
  ./configure --disable-all --enable-debug --enable-tokenizer
  make -j"$(nproc)"
  sapi/cli/php run-tests.php -q \
    Zend/tests/access_modifiers/ns_visibility_class_like_metadata.phpt \
    Zend/tests/access_modifiers/ns_visibility_class_like_syntax.phpt \
    Zend/tests/access_modifiers/ns_visibility_duplicate_modifier_error.phpt \
    Zend/tests/access_modifiers/ns_visibility_anonymous_class_error.phpt \
    Zend/tests/access_modifiers/ns_visibility_explicit_root_error.phpt \
    ext/tokenizer/tests/ns_visibility_tokens.phpt
'
```

Result: passed, 6/6 Phase B PHPT tests.

Phase C targeted tests:

```sh
sapi/cli/php run-tests.php -q \
  Zend/tests/access_modifiers/ns_visibility_class_like_metadata.phpt \
  Zend/tests/access_modifiers/ns_visibility_class_like_syntax.phpt \
  Zend/tests/access_modifiers/ns_visibility_duplicate_modifier_error.phpt \
  Zend/tests/access_modifiers/ns_visibility_anonymous_class_error.phpt \
  Zend/tests/access_modifiers/ns_visibility_explicit_root_error.phpt \
  ext/tokenizer/tests/ns_visibility_tokens.phpt \
  Zend/tests/access_modifiers/ns_visibility_runtime_new_static.phpt \
  Zend/tests/access_modifiers/ns_visibility_runtime_new_dynamic.phpt \
  Zend/tests/access_modifiers/ns_visibility_runtime_method_namespace.phpt \
  Zend/tests/access_modifiers/ns_visibility_runtime_segment_prefix.phpt \
  Zend/tests/access_modifiers/ns_visibility_runtime_new_cache_order.phpt
```

Result: passed, 11/11 PHPT tests.

Docker ZTS debug build:

```sh
docker compose -f docker/dev/compose.yml run --rm php-src-dev bash -lc '
  ./buildconf --force
  ./configure --disable-all --enable-debug --enable-zts --enable-tokenizer
  make -j"$(nproc)"
  sapi/cli/php -v
'
```

Result: build passed and reported `PHP 8.6.0-dev (cli) (ZTS DEBUG)`.
