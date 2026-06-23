# Reflection, Autoload, and Aliases

## Reflection Policy

Reflection is split into metadata and semantic operations.

| Operation | Policy |
| --- | --- |
| Read visibility metadata | Allowed |
| `new ReflectionClass($name)` | Allowed if the class can be resolved; no visibility denial |
| `getName()`, `getMethods()`, `getProperties()`, `getConstants()` | Allowed |
| `newInstance()` | Enforce class visibility using the call site's lexical namespace |
| `newInstanceArgs()` | Enforce class visibility using the call site's lexical namespace |
| `newInstanceWithoutConstructor()` | Enforce class visibility using the call site's lexical namespace |
| Constructor invocation during reflection construction | Constructor member visibility remains existing PHP behavior after class-level construction access succeeds |
| Static property/constant methods on ReflectionClass | Introspection allowed; mutation/invocation paths need per-operation classification |
| `ReflectionMethod::invoke()` | No class-level object membrane; member visibility follows existing Reflection/member rules |
| `ReflectionAttribute::newInstance()` | Attribute class access is checked against the lexical namespace of the attributed declaration |
| Privileged bypass | Not included in RFC v1 |

Reflection can reveal restricted class names. That is intentional and does not
grant construction or semantic class-name use.

`ReflectionAttribute::getName()` and `getAttributes()` remain metadata
operations. Instantiating the attribute object is the semantic operation:
`ReflectionAttribute::newInstance()` resolves the attribute class and checks it
against the namespace where the attribute was written, not the namespace where
reflection is called.

## Autoload Policy

For an unknown class name, namespace visibility metadata is unavailable until
autoload completes. RFC v1 therefore uses this order:

1. Resolve the class name according to existing PHP rules.
2. Run autoload if the operation currently autoloads.
3. If no CE is found, report the ordinary not-found result/error.
4. If a CE is found and the operation is semantic class-name use, run the
   namespace visibility check with the original operation's lexical namespace.

Autoload side effects may happen before an access error. The autoloader's own
namespace is not the caller namespace for the original operation.

Preventing autoload side effects would require a separate metadata manifest or
module/package system and is not part of RFC v1.

## Alias Policy

Visibility belongs to the target `zend_class_entry`, not to the string used to
find it.

```php
class_alias(Restricted::class, PublicLookingName::class);
```

The alias does not widen access:

- alias created in an allowed namespace: later denied use from another namespace
  still fails;
- alias created in a denied namespace: alias creation may reveal/load the class,
  but semantic use remains denied;
- alias before loading: existing autoload behavior applies, then the CE metadata
  is preserved;
- alias after loading: same CE metadata is preserved;
- alias through OPcache/preload: persisted CE metadata must survive.

## `::class`

`Restricted::class` produces a string and does not autoload or check access.
Any later operation that resolves that string to a CE for semantic use must
check visibility.

## Existence Probes

`class_exists()`, `interface_exists()`, `trait_exists()`, and `enum_exists()` may
return true for restricted declarations. They do not create a capability. A
later `new`, static access, type use, or inheritance operation must still check.
