# Risks and Backward Compatibility

## Syntax Risk

The base syntax is currently invalid, so direct source backward compatibility
risk is low. Tooling risk exists because tokenizers, parsers, formatters, and
IDEs must learn the new modifier.

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
is known only after class loading. This can trigger side effects. The RFC must
document that behavior if it is retained.

## Reflection Risk

If reflection construction bypasses visibility, the feature becomes weaker and
users may treat Reflection as an official escape hatch. If reflection enforces
visibility, some metaprogramming patterns fail for restricted classes. This is a
real RFC decision, not an implementation detail.

## Public API Risk

Allowing public APIs to expose restricted types may produce APIs that external
code can call but cannot name, extend, or implement. Forbidding inconsistent
accessibility may require earlier autoloading or more compile/link checks than
PHP currently performs.

First prototype recommendation: defer full consistent accessibility.

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

