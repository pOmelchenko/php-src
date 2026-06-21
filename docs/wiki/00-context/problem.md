# Problem Statement

PHP namespaces are commonly used to express architectural boundaries, but
current language visibility for class-like declarations is effectively public
at the namespace level. A project can place implementation details in
`Acme\Billing\Internal`, but the engine does not prevent unrelated code from
instantiating or extending those declarations.

The working problem is to determine whether PHP can support namespace-scoped
visibility for class-like declarations in a way that is useful, coherent with
existing PHP visibility, implementable in Zend Engine, testable, and compatible
with OPcache, reflection, autoloading, traits, dynamic names, and existing PHP
source code.

The motivating examples are:

```php
namespace Acme\Billing\Internal;

private(namespace) class ExactNamespaceOnly {}
protected(namespace) class NamespaceAndDescendants {}
```

The preliminary interpretation is:

- `private(namespace)` permits use only from the exact lexical namespace of
  the declaration.
- `protected(namespace)` permits use from the declaration namespace and its
  descendant namespaces.
- No modifier preserves current public semantics.

A future extension may allow an explicit ancestor root:

```php
namespace Acme\Billing\Infrastructure\Persistence;

protected(namespace: \Acme\Billing)
class UnitOfWork {}
```

That extension is not part of the first code prototype.

## Core Tension

PHP namespaces are name prefixes, not modules. Any PHP file can declare
`namespace Acme\Billing;`, even if it belongs to a different Composer package.
Therefore namespace visibility can protect design intent and ordinary use, but
it is not a security boundary for untrusted code.

The engine must also avoid a common false shortcut: once a class entry is
loaded, later forbidden use must still be rejected. Class-table caching,
runtime caches, OPcache persistence, class aliases, and reflection must not
turn a restricted class into a globally usable one.

## Required Outcome

The first iteration should produce a coherent research base:

- formal visibility rules;
- syntax comparison;
- prior-art review;
- full access matrix;
- php-src implementation map;
- phased implementation plan;
- PHPT plan;
- RFC draft;
- explicit open questions.

No implementation should be called complete until every path in the access
matrix has enforcement and tests.

