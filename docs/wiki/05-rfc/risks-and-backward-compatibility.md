# Risks and Backward Compatibility

## Syntax Risk

`private(namespace)` before named class-like declarations is currently invalid,
so direct source backward compatibility risk is low.

`protected(namespace)` is not proposed for class-like declarations in RFC v1.
The parser must reject it until a separate RFC chooses inheritance or descendant
semantics.

Tooling risk exists because tokenizers, parsers, formatters, and IDEs must learn
the new modifier.

## Semantic Risk

The feature touches many runtime paths. Incomplete enforcement would create
surprising bypasses through:

- dynamic class strings;
- aliases;
- reflection;
- type resolution;
- callables;
- OPcache runtime caches;
- preload;
- JIT helpers;
- unserialize;
- `::class`.

The implementation must not be called complete until all access-matrix paths
are covered.

## Autoload Risk

Forbidden access may still autoload the target before failing because metadata
is known only after class loading. This can trigger side effects. RFC v1 accepts
this limitation.

## Reflection Risk

RFC v1 allows Reflection metadata and enforces Reflection construction. No
privileged Reflection construction bypass is proposed.

## Public API Risk

Allowing public APIs to expose restricted types may produce APIs that external
code can call but cannot name, extend, or implement. Forbidding inconsistent
accessibility would require earlier autoloading or more compile/link checks than
PHP currently performs.

RFC v1 defers native consistent accessibility and recommends static analyzer
diagnostics.

## ABI/API Risk

Likely engine changes affect:

- `zend_class_entry` size or flags;
- possibly `zend_op_array` size if caller namespace is stored there;
- OPcache persistence format;
- Reflection APIs;
- tokenizer constants.

These are not patch-release changes.

## Security Risk

Users may misread namespace visibility as sandboxing. It is not. Any PHP file
can declare any namespace. This feature protects architectural intent under
normal project conventions, not untrusted-code isolation.

## Performance Risk

Performance is NOT MEASURED for the selected v1 model. RFC voting must wait for
Gate 5 evidence: public fast path, structure size impact, OPcache on/off, JIT
on/off where available, and multiple benchmark runs.
