# Compatibility

## Backward Compatibility

The base syntax is currently invalid PHP:

```php
private(namespace) class A {}
protected(namespace) class B {}
```

Therefore existing valid source should not change parse behavior by adding this
syntax. Compatibility risk comes from semantics around reflection, autoload,
class aliases, type resolution, and tools rather than from newly interpreting
valid existing code.

## Source Compatibility Risks

- Projects may already rely on architecture-only `Internal` namespaces being
  accessible from tests or framework glue. Those projects would opt in only by
  adding the new modifiers.
- If reflection construction enforces visibility, code that receives a
  restricted class name and constructs it reflectively will fail.
- If `class_exists()` remains allowed, existence-checking code still works, but
  later use may fail.
- If `::class` is restricted, common class-string patterns may break more than
  expected. This is why `::class` remains unresolved.

## Binary/API Compatibility Risks

Likely implementation changes include:

- new `zend_class_entry` flags;
- possibly new namespace/root string pointers on `zend_class_entry`;
- possibly namespace metadata on op arrays if class-like enforcement reuses the
  PR #20421 caller tracking approach;
- Reflection API additions;
- tokenizer token additions;
- OPcache persistence format changes.

These can be ABI-impacting and require explicit release-target analysis.

## OPcache Compatibility

Any metadata stored on `zend_class_entry` or `zend_op_array` must be persisted,
interned, restored, and invalidated correctly. Otherwise OPcache could:

- lose restrictions after caching;
- use stale namespace metadata;
- behave differently from non-OPcache execution;
- skip checks through JIT or optimized helper paths.

## Tooling Compatibility

Static analyzers, IDEs, formatters, doc generators, and tokenizer consumers need
updates for:

- new tokens;
- modifier ordering rules;
- reflection metadata;
- class-like visibility in API surface analysis;
- namespace refactoring when explicit roots are introduced.

## Security Compatibility

This feature must not be marketed as a sandbox. Any PHP code can declare a
foreign namespace. If untrusted code can run in the same PHP process, it can
declare the allowed namespace and attempt access.

