# Normative Semantics

This is proposed specification text, not discussion.

## Access Rule

For a class-like declaration declared with `private(namespace)`, access is
permitted if and only if the normalized lexical namespace of the operation is
equal to the normalized declaring namespace of the target class-like
declaration.

If access is not permitted, the operation fails when it resolves the target
`zend_class_entry`, unless it is rejected earlier by syntax or declaration
metadata.

## Namespace Representation

- The global namespace is represented as the empty string.
- A leading `\` is not part of a namespace value.
- Namespace names are compared as complete namespace segments after applying
  the same ASCII case normalization used for class-like symbol lookup.
- The original spelling may be preserved for diagnostics and Reflection.
- Namespace aliases affect target name resolution only. They do not change the
  lexical namespace of the operation.
- Bracketed and unbracketed namespace declarations produce the same namespace
  value.
- Multiple namespace blocks with the same normalized namespace have the same
  access.
- Files do not define a boundary. Code in another file with the same namespace
  has the same access.

## Lexical Caller

The caller namespace is determined from the source location of the operation:

| Context | Caller namespace |
| --- | --- |
| Top-level code | Namespace active for the compiled top-level op array; no namespace means global |
| Named function | Namespace of the function declaration |
| Method | Namespace of the class declaring the method body, except trait rules below |
| Closure | Namespace active where the closure is declared |
| Arrow function | Namespace active where the arrow function is declared |
| `eval()` without namespace declaration | Global namespace |
| `eval()` with namespace declaration | Declared namespace inside evaluated code |
| Trait composition `use T` | Namespace of the consuming class declaration |
| Operation written inside trait body | Namespace of the trait declaration |
| Inherited method body | Namespace of the method body that was inherited |
| Internal engine operation | Must either carry an originating lexical namespace or be explicitly classified as a metadata/probing operation |

`Closure::bind()`, `Closure::call()`, `$this`, `static::`, runtime call stack,
`debug_backtrace()`, autoloader namespace, and load order do not change the
caller namespace.

## Operations on Existing Objects

Class-level namespace visibility restricts semantic use of class-like names. It
is not an object membrane. Once an object exists, ordinary public object
operations do not check the object's concrete class visibility.

For example, external code may receive an object whose concrete class is
restricted and call public methods through a public interface. External code may
not use the restricted concrete class name in `new`, `instanceof`, type
declarations, static access, or equivalent class-name operations.

## Result Table

| Caller lexical namespace | Target declaration namespace | Result | Reason |
| --- | --- | --- | --- |
| `Acme\Billing` | `Acme\Billing` | Allowed | Exact normalized match |
| `Acme\Billing\Application` | `Acme\Billing` | Denied | Child namespace is a different namespace in v1 |
| `Acme` | `Acme\Billing` | Denied | Parent namespace is different |
| `Acme\Orders` | `Acme\Billing` | Denied | Sibling namespace is different |
| `Acme\BillingExtra` | `Acme\Billing` | Denied | Same textual prefix is not equality |
| global | global | Allowed | Empty string equals empty string |
| global | `Acme\Billing` | Denied | Empty string differs from named namespace |
| `Acme\Billing` | global | Denied | Named namespace differs from empty string |
| `acme\billing` | `Acme\Billing` | Allowed | Class-like lookup uses case-insensitive normalized keys |
| `Vendor\AliasUser` with `use Acme\Billing as B` | `Acme\Billing` | Denied | Alias resolves target names, not caller namespace |

## Error Timing

The normative timing principle is:

> Access is checked when the operation has resolved the target
> `zend_class_entry`, unless the operation is rejected earlier because of
> invalid syntax or declaration metadata.

Runtime denials throw `Error`. Compile-time or class-linking denials may use
existing fatal compile/link paths if the engine resolves the class entry at
that phase.

