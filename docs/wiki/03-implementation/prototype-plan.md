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

## Phase C: Central Access Check

Goal: one authoritative class-like visibility check.

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

## Commands Once Environment Is Ready

Suggested build/test sequence:

```sh
./buildconf
./configure --enable-debug --enable-zts --enable-opcache
make -j$(sysctl -n hw.ncpu)
TEST_PHP_ARGS="-q" make test TESTS="Zend/tests/access_modifiers"
```

Current iteration did not run these commands because the repository is not
configured, `sapi/cli/php` is absent, `re2c` is missing, and Bison is old.

