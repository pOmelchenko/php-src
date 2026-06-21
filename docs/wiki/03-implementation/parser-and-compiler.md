# Parser and Compiler Notes

## Minimal Parser Spike

Status: implemented in the working tree as an incomplete Phase B spike.

Target accepted syntax:

```php
private(namespace) class A {}
protected(namespace) class B {}
private(namespace) interface I {}
protected(namespace) trait T {}
private(namespace) enum E {}
```

Current accepted class-modifier order is prefix-only:

```php
private(namespace) final class A {}
protected(namespace) abstract class B {}
private(namespace) readonly class C {}
```

The following order is not accepted by the current spike:

```php
final private(namespace) class A {}
abstract protected(namespace) class B {}
```

This is a prototype limitation, not a settled language decision.

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

Current spike decision: dedicated scanner tokens are implemented:

- `T_PRIVATE_NAMESPACE`;
- `T_PROTECTED_NAMESPACE`.

## AST Metadata

The class-like declaration already uses `ZEND_AST_CLASS` plus declaration
flags. Ordinary class flags have little free space, so the current spike uses
`zend_ast_decl->attr` for parser-only namespace visibility metadata:

```c
ZEND_AST_CLASS_NAMESPACE_PRIVATE
ZEND_AST_CLASS_NAMESPACE_PROTECTED
ZEND_AST_CLASS_NAMESPACE_RESTRICTED
```

These are transferred into `ce_flags2` on `zend_class_entry` and stripped
from the AST-only representation by construction; they are never ORed into
ordinary `decl->flags`.

## Grammar Coverage

Current grammar only accepts `class_modifiers` before `T_CLASS`. Traits,
interfaces, and enums have separate productions without `class_modifiers`.

Therefore the implementation cannot only extend `class_modifier`. The current
spike chose a carefully constrained optional modifier prefix for each
declaration kind:

- `namespace_visibility_modifier class_modifiers_optional T_CLASS`;
- `namespace_visibility_modifier T_INTERFACE`;
- `namespace_visibility_modifier T_TRAIT`;
- `namespace_visibility_modifier T_ENUM`.

This kept Bison at zero parser conflicts in the Docker environment.

## Modifier Ordering

The desired eventual design may still include both orders:

```php
private(namespace) final class A {}
final private(namespace) class B {}
protected(namespace) readonly class C {}
abstract protected(namespace) class D {}
```

Order should either follow existing class modifier normalization or remain
restricted by grammar. If both orders are accepted later,
duplicate/conflicting modifier checks must produce stable errors.

Invalid combinations:

- both `private(namespace)` and `protected(namespace)`;
- namespace visibility on anonymous classes;
- namespace visibility repeated;
- `private(namespace)` with explicit-root syntax;
- `protected(namespace: Root)` in first parser spike.

## Declaration Namespace Metadata

During `zend_compile_class_decl()`, `CG(file_context).current_namespace` is
available. The current spike stores:

- normalized declaration namespace on `zend_class_entry`;
- optionally normalized visibility root, same as declaration namespace for base
  syntax;
- original spelling for diagnostics only if needed.

Global namespace is stored as an interned empty string for restricted
declarations. Unrestricted declarations keep the metadata pointer `NULL`.

## Compiler Output Tests

Parser/compiler spike tests should cover:

- accepted syntax for class/interface/trait/enum;
- rejected anonymous class modifier;
- rejected duplicate modifiers;
- rejected explicit root if not implemented;
- Reflection or debug dump showing metadata exists;
- no runtime enforcement claim beyond the implemented scope.
