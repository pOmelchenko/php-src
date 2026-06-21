# Working Model

This page states the current working hypothesis for the first prototype. It is
not an accepted PHP specification.

## Base Syntax

```php
private(namespace) class ExactNamespaceOnly {}
protected(namespace) class NamespaceAndDescendants {}
```

The same modifier family is intended for:

- classes;
- abstract classes;
- final classes;
- readonly classes;
- interfaces;
- traits;
- enums.

Anonymous classes do not receive top-level namespace visibility modifiers.

## Base Semantics

For a declaration in namespace `N`:

```text
private(namespace):   caller_namespace == N
protected(namespace): caller_namespace == N
                      or caller_namespace starts with N + "\\"
```

The comparison is by full namespace segments, so `Acme\BillingExtra` is not a
descendant of `Acme\Billing`.

## Caller Model

The check uses the lexical namespace of the operation. It must not depend on:

- `debug_backtrace()`;
- current object;
- namespace of outer user code;
- autoload order;
- whether the target class was already loaded.

Closures, arrow functions, eval, trait methods, internal functions, reflection,
and engine-generated operations each need explicit treatment.

## Boundary Model

The first prototype should use the name model: restrict use of the restricted
class-like name. It should not turn restricted classes into runtime membranes.

This must allow:

```php
namespace Acme\Billing;

public interface Service {}

private(namespace) final class ServiceImpl implements Service {}

public function createService(): Service {
    return new ServiceImpl();
}
```

External code may use the returned object through `Service`, but may not name
`ServiceImpl` directly unless its lexical namespace is allowed.

## Future Scope

The future explicit-root syntax is:

```php
protected(namespace: \Acme\Billing) class UnitOfWork {}
```

The root must be the declaration namespace or an ancestor by complete namespace
segments. Arbitrary unrelated roots, multiple roots, and friend namespaces are
future work.

## Current Warnings

- PHP namespaces are not a security boundary.
- Any file can declare another package's namespace.
- Class aliases must not remove restrictions.
- OPcache and runtime caches must not skip later checks.
- Reflection can inspect metadata, but construction and invocation semantics
  need an RFC decision.

