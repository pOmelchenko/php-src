# Test Matrix

No PHPT tests were added in this documentation-only iteration because no parser
or runtime code was implemented. This matrix defines required coverage.

## Syntax

| Category | Cases | Expected |
| --- | --- | --- |
| Parser accepted | `private(namespace)` and `protected(namespace)` before class/interface/trait/enum | parses after Phase B |
| Parser rejected | anonymous class with namespace visibility | parse error |
| Parser rejected | duplicate namespace visibility modifiers | compile error |
| Parser rejected | both private and protected namespace visibility | compile error |
| Parser rejected | `protected(namespace: Root)` before Phase F | parse error or explicit unsupported syntax error |
| Parser rejected | `internal class A` in first prototype | parse error |
| Modifier order | combinations with `abstract`, `final`, `readonly` | accepted or rejected according to chosen grammar, stable tests |

## Basic Visibility

| Category | Declaration | Caller | Expected |
| --- | --- | --- | --- |
| Private same namespace | `Acme\Billing\private(namespace) A` | `Acme\Billing` | allowed |
| Private child namespace | same | `Acme\Billing\App` | denied |
| Private sibling namespace | same | `Acme\Other` | denied |
| Protected same namespace | `Acme\Billing\protected(namespace) B` | `Acme\Billing` | allowed |
| Protected child namespace | same | `Acme\Billing\App` | allowed |
| Protected grandchild namespace | same | `Acme\Billing\App\Job` | allowed |
| Protected sibling namespace | same | `Acme\Other` | denied |
| Segment false positive | declaration `Acme\Billing` | caller `Acme\BillingExtra` | denied |
| Segment false positive | declaration `Acme\Billing` | caller `Acme\Bill` | denied |
| Global private | global declaration | global caller | allowed |
| Global private | global declaration | named caller | denied |
| Global protected | global declaration | named caller | depends on DEC-011; must not become public accidentally |

## Class-like Kinds

| Kind | Must test |
| --- | --- |
| class | construction, static access, inheritance |
| abstract class | inheritance and reflection |
| final class | construction and no modifier conflict |
| readonly class | modifier ordering |
| interface | implements and interface extends |
| trait | trait use and operations inside trait method |
| enum | enum cases, static access, `enum_exists()` |
| anonymous class | modifier rejected |

## Operations

| Operation | Required tests |
| --- | --- |
| `new ClassName()` | allowed/denied for static name |
| `new $className()` | allowed/denied dynamic string |
| `ClassName::method()` | class visibility before method call |
| `ClassName::$property` | class visibility before static property access |
| `ClassName::CONSTANT` | class visibility before class constant access |
| `ClassName::class` | chosen policy, no accidental autoload unless specified |
| first-class callable | allowed/denied resolution |
| string callable | allowed/denied through `call_user_func()` |
| array callable | allowed/denied class-string and object forms |
| `Closure::fromCallable()` | access checked at resolution |
| `is_callable()` | chosen false/throw behavior |
| `extends` | allowed same namespace, denied external |
| `implements` | allowed/denied restricted interface |
| `interface extends` | allowed/denied restricted interface |
| `trait use` | allowed/denied restricted trait |
| `instanceof` | chosen loaded/unloaded behavior |
| `catch` | chosen loaded/unloaded behavior |
| parameter type | allowed/denied type name use |
| return type | allowed/denied type name use |
| property type | allowed/denied type name use |
| typed class constant | allowed/denied type name use |
| union/intersection/DNF | each class-like component checked |
| promoted property | type name checked |
| attributes | class-name arguments and attribute class instantiation |
| `class_exists()` family | existence policy |
| `is_a()` / `is_subclass_of()` | existence vs use policy |
| `method_exists()` / `property_exists()` | probing policy |
| `defined()` for class constants | probing policy |
| `class_alias()` | alias preserves CE visibility |
| ReflectionClass | metadata visible |
| `ReflectionClass::newInstance()` | construction checked or documented bypass |
| `ReflectionMethod::invoke()` | no runtime membrane beyond member rules |
| `serialize()` | existing object allowed |
| `unserialize()` | class-name construction policy |
| `__set_state()` | class-name use through eval |
| clone existing object | allowed |
| preload | same as non-preload |
| OPcache | same as no OPcache |
| direct `require` | load does not grant access |
| autoload side effects | autoload can run before denial; caller remains original |

## Cache Order

Every operation that can cache a CE must have order tests:

1. allowed use first, denied use second;
2. denied use first, allowed use second.

Expected:

- first allowed use must not make later denied use succeed;
- first denied use must not poison later allowed use;
- alias and runtime cache variants must behave the same.

## Error Assertions

Tests should assert stable exception/error type and message text, but not
absolute file paths. Preferred runtime message form:

```text
Cannot access private(namespace) class Acme\Billing\Internal\Service from namespace App\Controller
```

