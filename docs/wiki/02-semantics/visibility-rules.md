# Visibility Rules

This page formalizes the base working model. It is not an accepted PHP
specification.

## Normalized Namespace

For access checks, a namespace should be normalized as follows:

- no leading backslash;
- no trailing backslash;
- global namespace represented as an empty string;
- comparison by complete namespace segments.

Case handling follows current PHP diagnostics: source spelling is preserved for
messages, while comparisons normalize internally. The implementation must not
expose canonical lower-case namespace strings in user-facing errors.

## `private(namespace)`

Given:

```text
declaration_namespace = D
caller_namespace      = C
```

Access is allowed exactly when:

```text
C == D
```

Examples for declaration namespace `Acme\Billing`:

| Caller namespace | Result |
| --- | --- |
| `Acme\Billing` | allowed |
| `Acme\Billing\Application` | denied |
| `Acme\BillingExtra` | denied |
| `Acme\Bill` | denied |
| global namespace | denied |

## `protected(namespace)`

For declaration namespace `D`, access is allowed when:

```text
C == D
```

or:

```text
C starts with D + "\\"
```

The prefix check is segment-aware.

Examples for declaration namespace `Acme\Billing`:

| Caller namespace | Result |
| --- | --- |
| `Acme\Billing` | allowed |
| `Acme\Billing\Application` | allowed |
| `Acme\Billing\Application\Jobs` | allowed |
| `Acme\BillingExtra` | denied |
| `Acme\Bill` | denied |
| `Acme` | denied |
| global namespace | denied |

## Explicit Root Future Scope

For:

```php
namespace Acme\Billing\Infrastructure\Persistence;

protected(namespace: \Acme\Billing) class UnitOfWork {}
```

The effective root is `Acme\Billing`. Access is allowed from `Acme\Billing` and
its descendants.

Rules to preserve:

- the root must equal the declaration namespace or be an ancestor by full
  namespace segments;
- unrelated roots are invalid;
- multiple roots are not in the first version;
- friend namespaces are not in the first version;
- global root must not accidentally make the declaration public.

## Global Namespace

For `private(namespace)` declared in the global namespace, access is allowed
only from code whose lexical namespace is also global.

For `protected(namespace)` declared in the global namespace, the naive prefix
rule would make every namespaced caller a descendant of the empty string. That
would turn the restriction into public access. This is unresolved and must be
decided explicitly. Candidate rules:

1. reject `protected(namespace)` in the global namespace;
2. treat global `protected(namespace)` the same as global
   `private(namespace)`;
3. allow it and document that it is effectively public, which is not preferred.

The current prototype preference is option 1 or 2, not option 3.

## Namespace Forms

The same lexical namespace should be recorded for:

- unbracketed namespace declarations;
- bracketed namespace declarations;
- multiple namespace blocks with the same name;
- code without a namespace declaration, which is global namespace;
- `eval()` code, using the namespace in the evaluated code if present, or the
  lexical/current namespace rules already used by the compiler if no namespace
  declaration is present.

## Composer Packages

Composer package ownership is not part of the language rule. A third-party file
can declare:

```php
namespace Acme\Billing;
```

Therefore namespace visibility is an architectural boundary for ordinary use,
not a security boundary against malicious or untrusted code.

## Security Statement

Any PHP file can declare another namespace. Namespace-scoped visibility can
reduce accidental coupling and enforce project architecture under normal
autoloading conventions, but it cannot isolate untrusted code.
