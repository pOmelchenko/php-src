# Operation Coverage

Invariant:

> Any semantic operation that uses a restricted class-like symbol must check
> access after resolving the target `zend_class_entry` and before completing the
> operation. Metadata/introspection probes may reveal existence, but they do not
> grant later semantic use.

## Matrix

| Operation | Resolves class entry | Loads class | Access check | Caller scope | Error stage | Test |
| --- | --- | --- | --- | --- | --- | --- |
| `new C()` | Yes | Yes if needed | Yes | Operation lexical namespace | Runtime/class fetch | OP-NEW-STATIC |
| `new $class` | Yes | Yes if needed | Yes | Operation lexical namespace | Runtime class fetch | OP-NEW-DYNAMIC |
| `C::method()` | Yes | Yes if needed | Yes before static method lookup | Operation lexical namespace | Runtime class fetch/call init | OP-STATIC-METHOD |
| `C::$property` | Yes | Yes if needed | Yes before static property lookup | Operation lexical namespace | Runtime class fetch | OP-STATIC-PROP |
| `C::CONST` | Yes except `class` | Yes if needed | Yes for constants other than `class` | Operation lexical namespace | Runtime class fetch | OP-CLASS-CONST |
| `C::class` | No CE required | No | No | N/A | None; later use checked | OP-CLASS-STRING |
| `extends C` | Yes | Yes if needed | Yes | Child declaration namespace | Class linking | OP-EXTENDS |
| `implements I` | Yes | Yes if needed | Yes | Implementing class namespace | Class linking | OP-IMPLEMENTS |
| `interface A extends I` | Yes | Yes if needed | Yes | Child interface namespace | Class linking | OP-IFACE-EXTENDS |
| Trait `use T` | Yes | Yes if needed | Yes | Consuming class namespace | Class linking | OP-TRAIT-USE |
| `instanceof C` | Yes if RHS loaded or dynamic | No for literal missing RHS | Yes when CE resolved | Operation lexical namespace | Runtime | OP-INSTANCEOF |
| `catch (C $e)` | Yes if current PHP resolves loaded CE | No autoload for literal missing RHS | Yes when CE resolved | Catch lexical namespace | Runtime exception matching | OP-CATCH |
| Parameter type | Yes when resolved | Yes if PHP loads type | Yes | Declaring function/method namespace | Compile/link/runtime type resolution | OP-TYPE-PARAM |
| Return type | Yes when resolved | Yes if PHP loads type | Yes | Declaring function/method namespace | Compile/link/runtime type resolution | OP-TYPE-RETURN |
| Property type | Yes when resolved | Yes if PHP loads type | Yes | Declaring class namespace | Class linking/type resolution | OP-TYPE-PROP |
| Typed class constant | Yes when resolved | Yes if PHP loads type | Yes | Declaring class namespace | Class linking/type resolution | OP-TYPE-CLASS-CONST |
| Promoted property | Yes when resolved | Yes if PHP loads type | Yes | Declaring class namespace | Class linking/type resolution | OP-TYPE-PROMOTED |
| Union | Yes per class-like arm | Yes if PHP loads type | Yes per restricted arm | Declaration namespace | Type resolution | OP-TYPE-UNION |
| Intersection | Yes per class-like arm | Yes if PHP loads type | Yes per restricted arm | Declaration namespace | Type resolution | OP-TYPE-INTERSECTION |
| DNF | Yes per class-like arm | Yes if PHP loads type | Yes per restricted arm | Declaration namespace | Type resolution | OP-TYPE-DNF |
| Attribute class | Yes on attribute instantiation/validation | Yes if needed | Yes | Namespace of attributed declaration | ReflectionAttribute::newInstance or validation | OP-ATTR-CLASS |
| Class name inside attribute argument | Usually no (`::class` string) | No for `::class` | No until later semantic use | N/A | Later operation | OP-ATTR-CLASS-STRING |
| First-class callable | Yes for class-string/static forms | Yes if needed | Yes at creation | Creation lexical namespace | Runtime callable creation | OP-CALLABLE-FIRST |
| String callable | Yes at validation/invocation | Yes if needed | Yes | Validation/invocation lexical namespace | Runtime | OP-CALLABLE-STRING |
| Array callable | Yes for class-string element; object form uses object CE only for method visibility | Yes if class-string | Yes for class-string target | Validation/invocation lexical namespace | Runtime | OP-CALLABLE-ARRAY |
| `Closure::fromCallable()` | Yes | Yes if needed | Yes | Call lexical namespace | Runtime | OP-CLOSURE-FROM-CALLABLE |
| `call_user_func()` | Yes during callable validation | Yes if needed | Yes | Call lexical namespace | Runtime | OP-CALL-USER-FUNC |
| `is_callable()` | Yes during validation | Yes depending current flags | No throw; inaccessible class-string callable returns false or records reason | Call lexical namespace | Runtime probe | OP-IS-CALLABLE |
| `class_exists()` | Yes/probes table | Optional autoload | No; existence may be revealed | N/A | Runtime probe | OP-CLASS-EXISTS |
| `interface_exists()` | Yes/probes table | Optional autoload | No; existence may be revealed | N/A | Runtime probe | OP-INTERFACE-EXISTS |
| `trait_exists()` | Yes/probes table | Optional autoload | No; existence may be revealed | N/A | Runtime probe | OP-TRAIT-EXISTS |
| `enum_exists()` | Yes/probes table | Optional autoload | No; existence may be revealed | N/A | Runtime probe | OP-ENUM-EXISTS |
| `is_a()` | Yes for string/object checks | Optional autoload | Yes when string target is semantic class use | Call lexical namespace | Runtime | OP-IS-A |
| `is_subclass_of()` | Yes | Optional autoload | Yes when string target/base is semantic class use | Call lexical namespace | Runtime | OP-IS-SUBCLASS |
| `method_exists()` | Yes for string class | Optional autoload | No; metadata probe | N/A | Runtime probe | OP-METHOD-EXISTS |
| `property_exists()` | Yes for string class | Optional autoload | No; metadata probe | N/A | Runtime probe | OP-PROPERTY-EXISTS |
| `class_alias()` | Yes for source class | Optional autoload | No to create alias; alias preserves CE visibility | N/A | Runtime alias creation | OP-CLASS-ALIAS |
| `ReflectionClass` | Yes | Yes if needed | No for metadata object creation | N/A | Runtime introspection | OP-REFLECTION-CLASS |
| Reflection instantiation | Uses stored CE | No if already reflected | Yes | Call lexical namespace | Runtime | OP-REFLECTION-NEW |
| Serialization | Uses object CE | No | No | N/A | Runtime object operation | OP-SERIALIZE |
| Unserialization | Yes from payload class name | Yes if needed | Yes before object allocation | `unserialize()` call lexical namespace | Runtime | OP-UNSERIALIZE |
| Cloning | Uses object CE | No | No class-level check | N/A | Runtime object operation | OP-CLONE |
| `__set_state` | Static call by class name after `eval`/user code | Yes if needed | Yes | Operation lexical namespace | Runtime static access | OP-SET-STATE |
| Direct `require` | Declares CE | Yes, by file inclusion | No use privilege from include itself | N/A | Compile/execute include | OP-REQUIRE |
| Preload | Declares/persists CE | Yes during preload | No use privilege; later operations check | Later operation namespace | Preload/link or runtime | OP-PRELOAD |

## Gate 3 Implementation Status

Gate 3 implements the runtime/linking/type/callable/reflection/serialization
rows that resolve a `zend_class_entry` during normal execution. It also keeps
`C::class`, existence probes, metadata Reflection construction, `method_exists`,
`property_exists`, `class_alias()` creation, `clone`, `serialize`, `get_class`,
and direct `require` as non-capability operations.

Covered by focused PHPTs:

- lexical caller context and main-op-array namespace ranges:
  `ns_visibility_gate3_lexical_context.phpt`,
  `ns_visibility_gate3_namespace_ranges.phpt`;
- static operations, `::class`, linking, `instanceof`, and `catch`:
  `ns_visibility_gate3_operations.phpt`;
- parameter, return, property, promoted property, typed class constant, union,
  intersection, and DNF type positions:
  `ns_visibility_gate3_type_positions.phpt`;
- callables, probe-only APIs, Reflection instantiation, aliases, existing-object
  operations, and `unserialize()`:
  `ns_visibility_gate3_callables_reflection_serialization.phpt`;
- direct include bypass guard:
  `ns_visibility_gate3_direct_require.phpt`.

Explicitly outside Gate 3:

- `ReflectionAttribute::newInstance()` and attribute validation timing. Attribute
  class strings remain string production until a later semantic operation.
- OPcache/preload/JIT behavioral validation. Gate 3 updates persistence plumbing
  for new op_array namespace metadata, but Gate 4 must prove optimized and
  persistent paths cannot bypass checks.
- Native consistent accessibility for public API leaks.
- Performance benchmarks.

## Already Obtained Object

```php
namespace Library;

public interface Service {
    public function execute(): void;
}

private(namespace) final class ServiceImpl implements Service {
    public function execute(): void {}
}

public function create(): Service {
    return new ServiceImpl();
}
```

External code:

```php
namespace Application;

$service = \Library\create();
$service->execute();
```

Results:

| Operation | Result |
| --- | --- |
| `$service->execute()` | Allowed; public member call on existing object |
| `clone $service` | Allowed unless class/member rules such as private `__clone()` deny it |
| `serialize($service)` | Allowed by class-level visibility |
| Dynamic public property read | Allowed by ordinary member rules |
| `get_class($service)` | Allowed; returns string |
| String comparison with class name | Allowed; strings are not hidden |
| `$service instanceof \Library\ServiceImpl` | Denied when RHS CE resolves from `Application` |

## Exceptions to the Invariant

Existence and metadata probes are the only intentional exception. They may
resolve a class entry without denying access, but they must not cache or return
a capability that later bypasses a semantic operation check.
