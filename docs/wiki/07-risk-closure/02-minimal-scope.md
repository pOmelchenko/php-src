# Minimal Scope

The first RFC must be useful, implementable, and testable without hidden
sub-features. Three scopes were compared.

## Scope A: Exact-only Class

```php
private(namespace) class Service {}
```

| Dimension | Assessment |
| --- | --- |
| Standalone value | Good for concrete internals and factories |
| Engine paths | Construction, static access, inheritance, type use, Reflection, aliases |
| Parser complexity | Lowest |
| Metadata complexity | One flag and declaring namespace for classes |
| Class-linking complexity | Parent and type positions only for classes |
| Reflection impact | Smallest |
| OPcache impact | Persist class metadata only |
| PHPT categories | Moderate |
| Incomplete enforcement risk | Lowest |
| Voting explanation | Very simple, but asks why interfaces/traits/enums are excluded |

## Scope B: Exact-only Class-like

```php
private(namespace) class Service {}
private(namespace) interface Contract {}
private(namespace) trait Helper {}
private(namespace) enum State {}
```

| Dimension | Assessment |
| --- | --- |
| Standalone value | Strong: class-like names are handled as one language family |
| Engine paths | Adds interface implementation, interface inheritance, trait use, enum existence/static paths |
| Parser complexity | Still one modifier production across named class-like declarations |
| Metadata complexity | Same `zend_class_entry` metadata applies to all named class-like declarations |
| Class-linking complexity | Higher, because traits/interfaces are linked differently |
| Reflection impact | Same `ReflectionClass` family surface |
| OPcache impact | Same CE persistence model |
| PHPT categories | Larger but still bounded |
| Incomplete enforcement risk | Medium; trait and interface paths must pass Gate 3 |
| Voting explanation | Coherent: the feature restricts class-like symbols, not only `class` |

## Scope C: Exact + Descendants + Root

```php
private(namespace) class Service {}
private(namespace: descendants) class SubtreeService {}
private(namespace: \Acme\Billing) class RootedService {}
```

| Dimension | Assessment |
| --- | --- |
| Standalone value | Highest convenience for deep package trees |
| Engine paths | Same as Scope B plus new root validation and descendant comparison |
| Parser complexity | Multiple new forms and likely independent votes |
| Metadata complexity | Needs mode plus root namespace; root must be validated as self or ancestor |
| Class-linking complexity | More failure cases and future compatibility constraints |
| Reflection impact | Must expose mode and root |
| OPcache impact | Persist more metadata |
| PHPT categories | Much larger: prefix, segment, root, global-root, case, alias |
| Incomplete enforcement risk | High |
| Voting explanation | Harder because descendants, roots, and syntax are separate policy choices |

## Decision

First RFC scope: **Scope B, exact-only class-like declarations**.

Scope B is the smallest coherent language feature for class-like symbols. Scope A
is a valid fallback if Gate 3 shows trait/interface/enum enforcement cannot be
completed, but starting with Scope B avoids an artificial split between classes
and the other named declarations stored as `zend_class_entry`.

Excluded from the first RFC:

- descendant namespaces;
- explicit root;
- `protected(namespace)`;
- `private class`;
- module/package/internal visibility;
- friend namespaces;
- native consistent accessibility.

