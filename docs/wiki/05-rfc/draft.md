# PHP RFC: Namespace-Scoped Visibility for Class-like Declarations

Version: 0.1-draft

Date: 2026-06-21

Author: TBD

Status: Draft

## Introduction

This RFC draft explores namespace-scoped visibility for PHP class-like
declarations. It is a research draft and has not been submitted to the PHP Wiki.

The proposed base syntax is:

```php
namespace Acme\Billing\Internal;

private(namespace) class ExactNamespaceOnly {}
protected(namespace) class NamespaceAndDescendants {}
```

The absence of a namespace visibility modifier preserves current public
semantics.

## Motivation

PHP projects often use namespaces such as `Internal`, `Infrastructure`, or
`Detail` to mark implementation-only classes. Today, these boundaries are
conventions. Any code can instantiate, extend, implement, or otherwise name
those declarations if it can load them.

The goal is to give library and application authors a language-level way to
express architectural boundaries while preserving PHP's dynamic loading model.

This feature is not a security sandbox. Any PHP file can declare another
namespace, so namespace-scoped visibility protects ordinary architecture, not
untrusted-code isolation.

## Proposal

Add namespace-scoped visibility modifiers for named class-like declarations:

```php
private(namespace)
protected(namespace)
```

`private(namespace)` restricts use of the declaration name to code in the exact
same lexical namespace.

`protected(namespace)` restricts use of the declaration name to code in the
declaration namespace or its descendant namespaces.

## Syntax

```php
private(namespace) class A {}
protected(namespace) class B {}
private(namespace) interface I {}
protected(namespace) trait T {}
private(namespace) enum E {}
```

The following is future scope:

```php
protected(namespace: \Acme\Billing) class UnitOfWork {}
```

The `internal` modifier is not proposed by this RFC draft.

## Semantics

For a declaration in namespace `D` and an operation in caller namespace `C`:

```text
private(namespace):   C == D
protected(namespace): C == D or C starts with D + "\\"
```

The prefix comparison is by complete namespace segments.

For declaration namespace `Acme\Billing`:

- `Acme\Billing` is allowed;
- `Acme\Billing\Application` is allowed for `protected(namespace)`;
- `Acme\BillingExtra` is not allowed;
- `Acme\Bill` is not allowed.

## Lexical Namespace

The caller namespace is the lexical namespace of the operation being compiled
or executed. It does not depend on:

- `debug_backtrace()`;
- current object;
- the namespace of the outer caller;
- autoload order;
- whether the target class has already been loaded.

Functions, methods, closures, arrow functions, eval, traits, reflection, and
internal functions that resolve class names need explicit engine handling.

## Supported Declarations

The intended supported declarations are:

- class;
- abstract class;
- final class;
- readonly class;
- interface;
- trait;
- enum.

Anonymous classes are not supported because they do not declare a stable
top-level name.

## Access Operations

The feature restricts use of class-like names. Operations requiring coverage
include:

- `new ClassName()`;
- `new $className()`;
- static method/property/constant access;
- first-class, string, and array callables;
- `Closure::fromCallable()`;
- `call_user_func()` and callable probes;
- `extends`, `implements`, interface extends, and trait use;
- `instanceof` and `catch`;
- parameter, return, property, class-constant, union, intersection, and DNF
  types;
- attributes that refer to class names;
- aliases;
- reflection construction;
- serialization and unserialization;
- OPcache, preload, and JIT paths.

## Dynamic Access

Dynamic class-string use must be checked when the string is resolved to a
class entry for an operation that uses the class-like declaration.

Existence probes such as `class_exists()` are an open design point. The current
draft direction is that visibility does not need to hide symbol existence, but
probes must not grant later access.

`SomeClass::class` is an open design point because PHP normally compiles it to a
string without autoloading.

## Inheritance and Types

Direct inheritance, interface implementation, interface extension, and trait use
must check the restricted declaration from the namespace of the declaration that
uses it.

Consistent accessibility for public APIs exposing restricted types is deferred
in this draft. PHP's autoloading and lazy type resolution make complete
compile-time enforcement difficult.

## Reflection

Reflection should be able to read namespace visibility metadata. Whether
Reflection construction methods are privileged is an explicit RFC decision.

The current draft preference is:

- allow metadata inspection;
- enforce visibility for `ReflectionClass::newInstance()`,
  `newInstanceArgs()`, and `newInstanceWithoutConstructor()`.

## Autoloading

If metadata for an unloaded class is needed, autoload may run before the access
error is reported. The caller namespace remains the namespace of the original
operation, not the namespace of the autoloader.

Directly requiring the file that declares a restricted class does not grant
permission to use that class name from a disallowed namespace.

## Error Behavior

Runtime violations should throw `Error`.

Preferred message shape:

```text
Cannot access private(namespace) class Acme\Billing\Internal\Service from namespace App\Controller
```

Compile-time or class-linking violations may use existing compile/link fatal
paths where appropriate. Stable messages should not include absolute file paths
unless an existing engine path requires them.

## Backward Incompatible Changes

The new syntax is currently invalid PHP, so existing valid code should not be
reinterpreted. The main compatibility risks are:

- reflection construction behavior;
- class-string and callable behavior;
- autoload side effects before denial;
- OPcache/JIT optimized paths;
- public APIs exposing restricted types.

## Proposed PHP Version

TBD. This is an ABI-impacting feature if it changes `zend_class_entry` or
`zend_op_array`, so it should target a future minor version only after engine
and RFC review.

## Impact on Extensions

Extensions may be affected by:

- new `zend_class_entry` flags or fields;
- new Reflection methods;
- tokenizer constants;
- class fetch APIs if caller namespace context is added;
- OPcache persistence changes.

No public Zend API is proposed in this draft.

## Impact on OPcache

OPcache must persist any new class-entry or op-array metadata and preserve
identical behavior with and without caching, preload, and JIT.

Runtime caches must not skip checks after a restricted class entry has been
resolved by allowed code.

## Performance

No benchmarks have been run.

The intended fast path is:

```c
if (!(ce->ce_flags & ZEND_ACC_NAMESPACE_RESTRICTED)) {
    return SUCCESS;
}
```

Unrestricted classes should pay at most a predictable flag check after class
entry resolution. Restricted classes require caller namespace lookup and string
comparison.

## Open Issues

- Case sensitivity and canonicalization of namespace comparisons.
- Global namespace behavior for `protected(namespace)`.
- `SomeClass::class` behavior.
- Existence and probing functions.
- Reflection bypass or enforcement.
- Public API consistent accessibility.
- Trait declaration namespace vs using class namespace.
- Exact error types for class-linking paths.
- OPcache/JIT cache-key design.

## Future Scope

- `protected(namespace: \Root)` where `Root` is the declaration namespace or an
  ancestor by full namespace segments.
- Namespace visibility for functions and constants.
- File-private visibility.
- Module/package-level `internal` if PHP gains a real module/package boundary.
- Friend namespaces or multiple roots.

## Rejected Alternatives

- Plain `private class` / `protected class` for the first prototype.
- `internal class` without a module boundary.
- Userland-only attributes as enforcement.
- Runtime membrane checks on every object method/property operation.

## Proposed Voting Questions

TBD. Possible votes:

1. Add `private(namespace)` and `protected(namespace)` for named class-like
   declarations.
2. Include interfaces, traits, and enums.
3. Chosen behavior for `SomeClass::class`.
4. Chosen behavior for reflection construction.
5. Chosen behavior for global `protected(namespace)`.

## References

- Namespace-Scoped Visibility for Methods and Properties:
  <https://wiki.php.net/rfc/namespace_visibility>
- Namespace Visibility for Class, Interface and Trait:
  <https://wiki.php.net/rfc/namespace-visibility>
- Attributes v2: <https://wiki.php.net/rfc/attributes_v2>
- Friends: <https://wiki.php.net/rfc/friends>
- Feature Proposals policy:
  <https://github.com/php/policies/blob/main/feature-proposals.rst>
- php-src PR #20421: <https://github.com/php/php-src/pull/20421>
- D specification: <https://dlang.org/spec/attribute.html>
- Rust Reference:
  <https://doc.rust-lang.org/reference/visibility-and-privacy.html>

## Changelog

- 0.1-draft, 2026-06-21: Initial repository-local research draft.

