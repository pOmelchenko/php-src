# Parser and Compiler Notes

## Minimal Parser Spike

Target accepted syntax:

```php
private(namespace) class A {}
protected(namespace) class B {}
private(namespace) interface I {}
protected(namespace) trait T {}
private(namespace) enum E {}
```

Target rejected syntax:

```php
private(namespace) new class {};
protected(namespace: \Acme\Billing) class FutureOnly {}
internal class A {}
private(namespace) private class A {}
```

The explicit-root form should stay in documentation and Future Scope for the
first code spike.

## Token Strategy

Possible implementation approaches:

1. Add scanner tokens similar to `T_PRIVATE_SET` and `T_PROTECTED_SET`, e.g.
   `T_PRIVATE_NAMESPACE` and `T_PROTECTED_NAMESPACE`.
2. Parse `T_PRIVATE '(' T_NAMESPACE ')'` as a grammar sequence.

The scanner-token approach is closer to PR #20421 and avoids broader grammar
ambiguity. It also lets tokenizer consumers see a single feature token, though
that requires tokenizer updates and tests.

## AST Flags

The class-like declaration already uses `ZEND_AST_CLASS` plus flags. A parser
spike should add flags such as:

```c
ZEND_ACC_NAMESPACE_PRIVATE
ZEND_ACC_NAMESPACE_PROTECTED
ZEND_ACC_NAMESPACE_RESTRICTED
```

Exact bit allocation must be audited against current `ce_flags` usage before
patching. The names above are placeholders for planning.

## Grammar Coverage

Current grammar only accepts `class_modifiers` before `T_CLASS`. Traits,
interfaces, and enums have separate productions without `class_modifiers`.

Therefore the implementation cannot only extend `class_modifier`. It must
either:

- introduce a new `class_like_visibility_modifiers` grammar fragment shared by
  class/interface/trait/enum, or
- duplicate a carefully constrained optional modifier prefix for each
  declaration kind.

The shared grammar is preferable if it keeps error messages predictable.

## Modifier Ordering

Valid examples should include:

```php
private(namespace) final class A {}
final private(namespace) class B {}
protected(namespace) readonly class C {}
abstract protected(namespace) class D {}
```

Order should either follow existing class modifier normalization or be
restricted by grammar. If both orders are accepted, duplicate/conflicting
modifier checks must produce stable errors.

Invalid combinations:

- both `private(namespace)` and `protected(namespace)`;
- namespace visibility on anonymous classes;
- namespace visibility repeated;
- `private(namespace)` with explicit-root syntax;
- `protected(namespace: Root)` in first parser spike.

## Declaration Namespace Metadata

During `zend_compile_class_decl()`, `CG(file_context).current_namespace` is
available. A metadata spike should store:

- normalized declaration namespace on `zend_class_entry`;
- optionally normalized visibility root, same as declaration namespace for base
  syntax;
- original spelling for diagnostics only if needed.

Global namespace should be stored as `NULL` or an interned empty string, but the
choice must be consistent with OPcache persistence and fast-path checks.

## Compiler Output Tests

Parser/compiler spike tests should cover:

- accepted syntax for class/interface/trait/enum;
- rejected anonymous class modifier;
- rejected duplicate modifiers;
- rejected explicit root if not implemented;
- Reflection or debug dump showing metadata exists;
- no runtime enforcement claim beyond the implemented scope.

