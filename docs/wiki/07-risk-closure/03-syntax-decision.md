# Syntax Decision

The first RFC syntax is:

```php
private(namespace) class A {}
private(namespace) interface I {}
private(namespace) trait T {}
private(namespace) enum E {}
```

## Bake-off

| Syntax | Current RFC conflict | Keyword meaning | Grammar ambiguity | Future file/module visibility | Readability | Tooling/IDE | Future Scope | Vote |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `private class A {}` | Conflicts with private classes/functions draft | Strongly suggests file/private block or class-private | High with class modifiers | Blocks `private(file)` spelling | Short but ambiguous | Tools may already track draft | Bad for namespace v1 | Separate rejected syntax |
| `private(namespace) class A {}` | Matches member RFC spelling without taking class-level future there | Qualified private means namespace axis | Low if parsed as qualified modifier | Leaves `private(file)` and `private(module)` open | Explicit | Requires tokenizer/parser updates | Good | Primary v1 vote |
| `protected(namespace) class A {}` | Conflicts with member RFC Future Scope | Inheritance-protected is expected | High semantic ambiguity | Poor for descendants | Misleading | Hard to explain | Defer | Separate vote only |
| `private(namespace, descendants) class A {}` | No current RFC conflict | Qualified private plus mode | Parser more complex | Compatible | Verbose | New grammar | Possible RFC B | Separate vote |
| `private(namespace: descendants) class A {}` | No current RFC conflict | Named argument-like mode | Parser more complex | Compatible | Clearer than comma | New grammar | Possible RFC B | Separate vote |
| `private(namespace: \Acme\Billing) class A {}` | No current RFC conflict | Explicit root | Parser more complex; root validation needed | Compatible | Clear but refactor-sensitive | New grammar | RFC C | Separate vote |
| `internal class A {}` | Conflicts with Reflection terminology and lacks module boundary | Means module/package in other languages | Contextual keyword risk | Better reserved for modules | Readable but wrong boundary | New keyword | RFC D | Separate vote |
| `package class A {}` | No PHP boundary exists | Suggests package system | Contextual keyword risk | Could fit future modules | Misleading today | New keyword | RFC D | Separate vote |
| `#[VisibleFrom(...)] class A {}` | Avoids modifier RFCs | Attribute looks optional | No grammar issue | Extensible | Stringly typed | Easier parsing, but engine-special | Possible alternative | Separate vote |

## Required Answers

1. Plain `private class` is not used because the private classes/functions draft
   already uses it for file or namespace-block privacy through name mangling.
   Namespace visibility needs runtime CE checks and must remain distinguishable
   from future `private(file)`.
2. Descendant semantics cannot be silently named `protected(namespace)` because
   `protected` in PHP means inheritance visibility, and the active member RFC
   reserves `protected(namespace)` Future Scope for namespace plus inheritance,
   not namespace subtree access.
3. Class-level syntax should match member-level `private(namespace)` for the
   exact namespace rule. The shared spelling means "private to the exact lexical
   namespace" on the namespace axis, while the declaration kind defines what is
   being restricted.
4. Descendant syntax must be voted separately. It changes namespace semantics
   from exact names to a hierarchy and requires a new spelling.
5. Explicit root can be added later without a BC break if v1 accepts only
   `private(namespace)` and rejects `private(namespace: ...)`.

## Decision

RFC v1 uses only `private(namespace)` on named class-like declarations.

`protected(namespace)` is rejected from RFC v1 and reserved until an inheritance
axis design and a descendant syntax bake-off are separately accepted.

