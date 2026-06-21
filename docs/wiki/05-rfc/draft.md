# PHP RFC: Exact Namespace Visibility for Class-like Declarations

Version: 0.2-draft

Date: 2026-06-21

Author: Research draft, unassigned

Status: Research draft

## Introduction

This RFC draft proposes exact namespace visibility for named class-like
declarations using the qualified modifier `private(namespace)`.

```php
namespace Acme\Billing;

private(namespace) class InternalService {}
```

The declaration may be used only by code whose lexical namespace is exactly the
same namespace as the declaration. Child namespaces do not receive access in
this RFC.

This draft is not a submitted PHP RFC and is not a specification of current PHP.

## Motivation

PHP projects often use namespaces such as `Internal`, `Infrastructure`, or
`Detail` to mark implementation classes. Today this is convention only. Any code
that can load the class can instantiate it, extend it, name it in type
declarations, or access it statically.

`private(namespace)` gives libraries and applications an engine-enforced way to
mark class-like declarations as internal to one exact namespace while preserving
PHP's file-by-file loading model.

This is not a security sandbox. Any PHP file can declare the same namespace.

## Proposal

Add `private(namespace)` as a modifier for named class-like declarations:

```php
private(namespace) class A {}
private(namespace) interface I {}
private(namespace) trait T {}
private(namespace) enum E { case X; }
```

The absence of `private(namespace)` preserves current public class-like
declaration behavior.

Anonymous classes are not supported because they do not declare a stable
top-level class-like symbol.

## Syntax

The modifier applies before the class-like declaration keyword:

```php
private(namespace) final class A {}
private(namespace) readonly class B {}
private(namespace) interface I {}
private(namespace) trait T {}
private(namespace) enum E {}
```

`protected(namespace)` is not part of this RFC. `private class`, `internal
class`, `package class`, descendant modes, explicit root modes, and attributes
are not part of this RFC.

The parser must reject duplicate or conflicting class-level namespace
visibility modifiers.

## Exact Namespace Rule

For a target declaration namespace `D` and an operation lexical namespace `C`,
access is permitted if and only if:

```text
normalize(C) == normalize(D)
```

The global namespace is represented as the empty string. Leading `\` is not part
of the namespace value. Comparisons use the same case-normalized namespace
spelling used for class-like symbol lookup; original spelling may be preserved
for diagnostics and Reflection.

Namespace aliases affect target name resolution only. They do not change the
caller lexical namespace.

## Lexical Namespace

The caller namespace is the lexical namespace of the operation. It does not
depend on:

- runtime call stack;
- `debug_backtrace()`;
- current object;
- namespace of the outer caller;
- namespace of the autoloader;
- class load order;
- whether the class entry was cached.

Top-level code uses the namespace active for the compiled top-level op array.
Code without a namespace declaration uses the global namespace. `eval()` without
a namespace declaration uses the global namespace; `eval()` with a namespace
declaration uses the namespace declared in the evaluated code.

For trait composition, `use RestrictedTrait` is checked from the namespace of
the consuming class declaration. Operations written inside a trait body use the
namespace of the trait declaration.

## Checked Operations

The feature restricts semantic use of restricted class-like symbols. The access
check runs after the operation resolves the target `zend_class_entry` and before
the operation completes.

Checked operations include:

- `new C()` and `new $class`;
- static method, property, and class constant access;
- `extends`, `implements`, interface extends, and trait `use`;
- class-like names in parameter, return, property, class constant, promoted
  property, union, intersection, and DNF types;
- `instanceof` and `catch` when the target class entry is resolved;
- callable resolution involving class strings;
- Reflection instantiation;
- unserialization of restricted class names;
- attribute class instantiation.

`C::class` is not checked and does not autoload. It produces a string. Later
semantic use of that string is checked.

Existence and metadata probes such as `class_exists()` and
`new ReflectionClass()` may reveal restricted declarations. They do not grant
permission for later semantic use.

## Already Obtained Objects

Class-level namespace visibility is not an object membrane. Once an object has
been obtained, public object operations remain governed by ordinary member
visibility.

```php
namespace Library;

public interface Service {
    public function execute(): void;
}

private(namespace) final class ServiceImpl implements Service {
    public function execute(): void {}
}

public function create(): Service {
    return new ServiceImpl();
}

namespace Application;

$service = \Library\create();
$service->execute(); // allowed
```

External code still cannot name `Library\ServiceImpl` in `new`, `instanceof`,
type declarations, inheritance, static access, or equivalent operations.

Cloning, serialization, dynamic public property access, `get_class()`, and
string comparison are not class-level visibility checks.

## Types and Public API Exposure

Type declarations are semantic use of class-like names when their target class
entry is resolved. The caller namespace is the namespace of the declaration that
contains the type.

This RFC does not implement native consistent accessibility. A public API may
expose a restricted type if that API is declared in a namespace allowed to name
the type. External code may call the API and receive values, but cannot name the
restricted type in its own semantic operations.

Static analyzers are encouraged to diagnose public API exposure of restricted
types as an architectural leak.

## Reflection

Reflection metadata access is allowed:

- `new ReflectionClass($name)`;
- `getName()`;
- `getMethods()`;
- `getProperties()`;
- `getConstants()`;
- namespace visibility metadata methods.

Reflection construction methods must enforce class-level namespace visibility:

- `ReflectionClass::newInstance()`;
- `ReflectionClass::newInstanceArgs()`;
- `ReflectionClass::newInstanceWithoutConstructor()`.

No privileged Reflection bypass is part of this RFC.

`ReflectionMethod::invoke()` is not a class-level object membrane check. It
continues to follow Reflection and member-visibility behavior.

## Aliases

Visibility metadata belongs to the target `zend_class_entry`. `class_alias()`
does not widen visibility.

An alias created in an allowed namespace, denied namespace, before loading,
after loading, through autoload, or under OPcache still resolves to a class
entry whose namespace visibility metadata must be checked on semantic use.

## Autoloading

For unknown class names, metadata is known only after the class is loaded.
Autoload may therefore run before an access error.

The access check uses the lexical namespace of the original operation, not the
namespace of the autoloader.

Preventing autoload side effects requires a separate metadata manifest or
module/package system and is not part of this RFC.

## Runtime Cache, OPcache, and Preload

Runtime caches must not bypass visibility. If a class entry was resolved and
cached by code in an allowed namespace, later code in a denied namespace must
perform its own access check before semantic use.

The implementation must cover:

- opcode runtime caches;
- class-entry cache on strings;
- class table aliases;
- inheritance cache;
- callable/fcall caches;
- Reflection objects storing class entries;
- OPcache persistent class entries;
- preloaded classes;
- JIT helpers and assumptions.

OPcache must persist namespace visibility metadata. Preload must not remove or
widen visibility.

## Errors

Runtime access violations throw `Error`.

Preferred message shape:

```text
Cannot access private(namespace) class Acme\Billing\InternalService from namespace App
```

Compile-time or class-linking failures may use existing fatal compile/link
paths where PHP already resolves the class entry during compilation or linking.

## Backward Incompatible Changes

The proposed syntax is currently invalid for class-like declarations. Existing
valid PHP source is not reinterpreted.

Compatibility impact exists for:

- tokenizer/parser/formatter/IDE support;
- static analyzers;
- Reflection metadata;
- extension ABI if `zend_class_entry` or class fetch APIs change;
- OPcache persistence format;
- code generators that need to parse or emit class-like declarations.

Compatibility of third-party tools is not claimed without their own tests.

## Security

Namespace visibility does not establish namespace ownership and is not a
security boundary. It does not protect against code that intentionally declares
the same namespace.

The feature is for architectural enforcement in cooperating codebases.

## Implementation Status

Current local prototype status: incomplete experimental Phase B/C spike.

Implemented in the spike:

- parser and metadata support for `private(namespace)` and
  `protected(namespace)`;
- Reflection metadata methods;
- partial OPcache metadata persistence;
- partial runtime enforcement for `new` and `ZEND_FETCH_CLASS`.

Not complete for this RFC:

- v1 must reject `protected(namespace)` for class-like declarations;
- namespace metadata must be normalized for access comparison;
- trait body operations need original trait declaration namespace metadata;
- static access, inheritance, type resolution, `instanceof`, `catch`,
  callables, aliases, Reflection instantiation, unserialization, OPcache,
  preload, and JIT are not fully enforced;
- performance is not measured.

## Performance

Performance evidence is NOT MEASURED.

The intended design requires a public fast path for unrestricted class-like
declarations and reproducible benchmarks before voting.

## Rejected Alternatives

- Plain `private class`: reserved for file or namespace-block privacy in the
  private classes/functions draft.
- `protected(namespace)`: conflicts with inheritance terminology and the active
  member RFC's Future Scope.
- Descendant visibility in v1: deferred because it adds namespace hierarchy,
  segment comparison, root/global rules, and separate syntax choices.
- Explicit root in v1: deferred because exact namespace visibility is useful
  without it and root validation is separate.
- `internal class`: reserved for a future module/package boundary.
- Attribute syntax: rejected for v1 because engine enforcement still needs
  parser/compiler/runtime integration and string roots are refactor-sensitive.
- Runtime object membrane: rejected because the feature restricts class-like
  names, not already obtained objects.
- Native consistent accessibility: deferred because it expands class-linking,
  autoload, variance, and API graph analysis.

## Future Scope

- Namespace subtree visibility with syntax selected separately.
- Explicit ancestor root.
- Module/package-level `internal`.
- Friend namespaces or friend classes.
- Function and constant namespace visibility.
- Native consistent accessibility diagnostics.
- Optional privileged Reflection bypass, if explicitly voted.

## Proposed Voting Question

Primary vote, 2/3 majority:

> Add `private(namespace)` exact namespace visibility for named class-like
> declarations (`class`, `interface`, `trait`, and `enum`)?

Independent future votes are required for descendants, explicit roots,
`protected(namespace)`, modules/internal, and native consistent accessibility.

## References

- <https://wiki.php.net/rfc/namespace_visibility>
- <https://github.com/php/php-src/pull/20421>
- <https://wiki.php.net/rfc/namespace-visibility>
- <https://wiki.php.net/rfc/private-classes-and-functions>
- <https://wiki.php.net/rfc/encapsulation>
- <https://externals.io/message/127466>
- <https://externals.io/message/51562>
- <https://externals.io/message/119893>
- <https://wiki.php.net/rfc/friend-classes>
- <https://wiki.php.net/rfc/voting>

## Changelog

- 0.2-draft: Narrowed proposal to exact-only `private(namespace)` class-like
  declarations; moved descendants, explicit root, and `protected(namespace)` out
  of Proposal.
- 0.1-draft: Initial research draft with exact and descendant ideas.
