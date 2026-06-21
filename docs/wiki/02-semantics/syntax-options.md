# Syntax Options

The selected v1 candidate is:

```php
private(namespace) class A {}
protected(namespace) class B {}
```

This is a prototype and RFC draft choice, not an accepted PHP language
decision. `protected(namespace)` carries an explicit terminology risk because
PHP already uses `protected` for inheritance visibility.

## Option A: Plain Modifiers

```php
private class A {}
protected class B {}
```

| Criterion | Assessment |
| --- | --- |
| Current PHP visibility | Looks familiar, but PHP currently uses `private` and `protected` for members, not top-level class-like declarations. |
| Inheritance-based `protected` conflict | High. `protected class` may be read as subclass visibility instead of namespace-tree visibility. |
| File-private draft conflict | High. Plain top-level `private` naturally suggests `private(file)` in languages such as Kotlin and Swift. |
| Future `private(file)` | Harder, because the short spelling would already be consumed. |
| Future module system | Ambiguous. Does `private` mean namespace, file, module, or package? |
| Parser ambiguity | Manageable, but requires allowing member visibility tokens before class-like declarations. |
| New token | No new keyword, but grammar meaning changes. |
| Backward compatibility | Currently invalid syntax would become valid. Low source break, but higher conceptual break. |
| Readability | Short, but under-specified at the call site. |
| Namespace refactoring | No explicit namespace reference. |
| IDE/static analysis | Easy to parse, hard to explain precisely. |
| Extend to functions/constants/members | Could extend, but the meaning collision remains. |

Conclusion: not preferred for first prototype.

## Option B: Qualified Namespace Modifiers

```php
private(namespace) class A {}
protected(namespace) class B {}
```

| Criterion | Assessment |
| --- | --- |
| Current PHP visibility | Reuses existing visibility words while explicitly qualifying the axis. |
| Inheritance-based `protected` conflict | Still material; v1 must explicitly state that class-level `protected(namespace)` means namespace subtree, not inheritance. |
| File-private draft conflict | Lower because future `private(file)` remains available. |
| Future `private(file)` | Natural extension point. |
| Future module system | Can coexist with `private(module)` or `internal` if a module boundary appears. |
| Parser ambiguity | Similar to `private(set)` and `protected(set)` scanner strategy already present in current PHP. |
| New token | For v1, `T_PRIVATE_NAMESPACE` and `T_PROTECTED_NAMESPACE` are needed. |
| Backward compatibility | Currently invalid syntax becomes valid. No new reserved keyword. |
| Readability | Explicit and local. |
| Namespace refactoring | No namespace string in base form. |
| IDE/static analysis | Clear, provided tools learn the new modifier. |
| Extend to functions/constants/members | Good. The qualifier can be reused across declaration kinds. |

Conclusion: preferred for the first prototype.

## Option C: `internal`

```php
internal class A {}
```

| Criterion | Assessment |
| --- | --- |
| Current PHP visibility | Familiar from C#, Kotlin, Swift, but not from PHP. |
| Inheritance-based `protected` conflict | None. |
| File-private draft conflict | Low. |
| Future `private(file)` | Unaffected. |
| Future module system | Strong fit only if PHP gains a real module/package boundary. |
| Parser ambiguity | Requires a new contextual keyword or reserved token. |
| New token | Yes. |
| Backward compatibility | Possible conflict with existing identifiers depending on token design. |
| Readability | Good if module boundary exists; misleading if it means namespace. |
| Namespace refactoring | No namespace string. |
| IDE/static analysis | Easy after tools know module identity. |
| Extend to functions/constants/members | Possible, but semantics need module identity first. |

Conclusion: reserve for future modules/packages. Do not add to the first
prototype because PHP namespaces and Composer packages are not the same thing.

## Option D: Explicit Root

```php
protected(namespace: \Acme\Billing) class A {}
```

| Criterion | Assessment |
| --- | --- |
| Current PHP visibility | Explicitly qualified. |
| Inheritance-based `protected` conflict | Low; the namespace argument clarifies the access axis. |
| File-private draft conflict | Low. |
| Future `private(file)` | Compatible. |
| Future module system | Compatible but distinct. |
| Parser ambiguity | Higher than Option B because it introduces an argument-like modifier form. |
| New token | Probably yes, or parser support for qualified modifier arguments. |
| Backward compatibility | Currently invalid syntax becomes valid. |
| Readability | Strong when the allowed root differs from the declaration namespace. |
| Namespace refactoring | Harder because the root is a source-level namespace name. |
| IDE/static analysis | Good with full-name resolution and refactoring support. |
| Extend to functions/constants/members | Good, but complexity is higher. |

Conclusion: future scope. It should be added only after base
`private(namespace)` and `protected(namespace)` semantics are stable.

## Option E: Built-in Attribute

```php
#[VisibleFrom('Acme\\Billing')]
class A {}
```

| Criterion | Assessment |
| --- | --- |
| Current PHP visibility | Avoids grammar-level visibility words, but visibility becomes metadata-shaped. |
| Inheritance-based `protected` conflict | None if the attribute name is clear. |
| File-private draft conflict | Low. |
| Future `private(file)` | Separate feature. |
| Future module system | Could be adapted, but weak as core syntax. |
| Parser ambiguity | Low for ordinary attributes. |
| New token | No new keyword if implemented as an attribute class. |
| Backward compatibility | Attribute names are ordinary class names unless made compiler-special. |
| Readability | Verbose and less integrated with language visibility. |
| Namespace refactoring | String arguments are fragile; class-string arguments alter autoload/name-resolution behavior. |
| IDE/static analysis | Possible, but tools need special knowledge. |
| Extend to functions/constants/members | Mechanically easy, semantically weaker. |

Conclusion: useful as an alternative to document, but not preferred. A userland
attribute cannot enforce engine access. A built-in attribute would still need
parser/compiler/runtime support and would make a core visibility rule look
optional.

## Prototype Syntax Decision

Use Option B for a parser and metadata spike:

```php
private(namespace)
protected(namespace)
```

Keep Option D in Future Scope and reserve `internal` for a future module or
package boundary.
