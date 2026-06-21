# Runtime and Caches

The implementation must find every path that produces or reuses a
`zend_class_entry`. The check cannot rely on one opcode.

## Class-entry Sources

| Path | File/function | Operation categories | Autoload behavior | Cache | Check requirement |
| --- | --- | --- | --- | --- | --- |
| General lookup | `Zend/zend_execute_API.c:zend_lookup_class_ex()` | Many probes and semantic operations | Yes unless `NO_AUTOLOAD` or compiling | CE cache on strings and class table | Do not check blindly; callers include probes. Semantic wrappers must check after return, including cache hits |
| General fetch | `zend_fetch_class()`, `zend_fetch_class_by_name()` | `new`, static access, dynamic names | Yes depending flags | May use CE cache via lookup | Check in callers or access-aware wrapper |
| `ZEND_NEW` | `Zend/zend_vm_def.h` | Object construction | Yes | Opcode runtime cache | Check after cached or fetched CE before `object_init_ex()` |
| `ZEND_FETCH_CLASS` | `Zend/zend_vm_def.h` | Dynamic class fetch for later operations | Yes | Opcode runtime cache | Check after cached or fetched CE |
| Static method/property/constant | `ZEND_INIT_STATIC_METHOD_CALL`, `ZEND_FETCH_STATIC_PROP_*`, `ZEND_FETCH_CLASS_CONSTANT` | Static use | Yes | Opcode runtime caches | Check before method/property/constant lookup |
| `instanceof` | `ZEND_INSTANCEOF` | Type check | Literal missing RHS does not autoload | Opcode cache when loaded | Check when RHS CE is resolved |
| `catch` | `ZEND_CATCH` | Exception matching | Uses no-autoload silent lookup | Opcode cache | Check when CE is resolved |
| Inheritance/linking | `Zend/zend_inheritance.c:zend_do_link_class()` | `extends`, `implements`, trait `use` | Yes | Inheritance cache and class table | Check parent/interface/trait CE before linking/composition |
| Early binding | `Zend/zend_compile.c:zend_compile_class_decl()` | Compile-time parent binding | No autoload | Compile-time class table | Check if parent CE is found |
| Type declarations | `Zend/zend_compile.c` type resolution and inheritance checks | Parameter/return/property/constant types | Lazy/phase dependent | Type caches | Check each resolved class-like type CE |
| Callables | `Zend/zend_API.c:zend_is_callable_ex()` and related helpers | Callable validation/invocation | Yes depending form | fcall cache | Check class-string targets from caller frame |
| Existence probes | `Zend/zend_builtin_functions.c` | `class_exists`, `method_exists`, etc. | Optional | Class table | No denial, but no capability cache bypass |
| `class_alias()` | `Zend/zend_builtin_functions.c` | Alias creation | Optional source autoload | Class table alias to same CE | Preserve CE metadata; semantic use through alias checks |
| Reflection | `ext/reflection/php_reflection.c` | Metadata, instantiation, attributes | Yes | Reflection object stores CE | Introspection allowed; instantiation checked |
| Serialization | `ext/standard/var_unserializer*` | `unserialize()` | Yes via incomplete/autoload paths | Class table | Check before allocating restricted class object |
| Constants API | `Zend/zend_constants.c` | `constant("C::X")` | Yes if class constant | Constant caches | Check class CE for class constants |
| OPcache preload | `ext/opcache/ZendAccelerator.c` | Preload/link/cache replay | Yes during preload | Persistent classes/inheritance cache | Persist metadata; check later operations |
| JIT helpers | `ext/opcache/jit/zend_jit_helpers.c`, `zend_jit.c` | Optimized class fetch/static paths | Mirrors VM | JIT assumptions | Do not generate bypass for restricted CE |

## Coverage Map

| php-src path | Operation categories | Check added | PHPT | OPcache test | Status |
| --- | --- | --- | --- | --- | --- |
| `ZEND_NEW` | `new C`, `new $class`, cached `new` | Partial in Phase C | Existing Phase C `new` tests | No | MITIGATED |
| `ZEND_FETCH_CLASS` | Dynamic class fetch and some static prep | Partial in Phase C | Existing dynamic tests | No | MITIGATED |
| Static handlers | static method/property/constant | No | Planned `OP-STATIC-*` | Planned | NOT IMPLEMENTED |
| Inheritance/linking | `extends`, `implements`, trait `use` | No | Planned `OP-EXTENDS`, `OP-IMPLEMENTS`, `OP-TRAIT-USE` | Planned | NOT IMPLEMENTED |
| Type resolution | all type positions | No | Planned `OP-TYPE-*` | Planned | NOT IMPLEMENTED |
| `instanceof`/`catch` | runtime type checks | No | Planned `OP-INSTANCEOF`, `OP-CATCH` | Planned | NOT IMPLEMENTED |
| Callables | first-class/string/array/call_user_func | No | Planned `OP-CALLABLE-*` | Planned | NOT IMPLEMENTED |
| Existence probes | class/interface/trait/enum/method/property exists | No denial by design | Planned probe tests | Planned for cache behavior | RESOLVED-POLICY |
| Reflection | metadata and construction | Metadata only in Phase B; construction not checked | Planned `OP-REFLECTION-*` | Planned | NOT IMPLEMENTED |
| Aliases | `class_alias()` | Metadata belongs to CE; no full tests | Planned alias tests | Planned | NOT IMPLEMENTED |
| Unserialize | payload class names | No | Planned `OP-UNSERIALIZE` | Planned | NOT IMPLEMENTED |
| OPcache/preload/JIT | optimized/persistent CE | OPcache persistence spike only | Not run | Planned | NOT IMPLEMENTED |

`NOT IMPLEMENTED` means the semantic decision is closed but the C prototype has
not passed the relevant gate.

## Acceptance Criterion

> Class entry, разрешённый и закешированный одним namespace, не должен
> становиться доступным другому namespace без повторной проверки.

Therefore:

- opcode runtime cache hits must run the access check again;
- CE cache hits from `zend_lookup_class_ex()` must be followed by access checks
  in semantic callers;
- class-table alias entries must point to the same restricted CE metadata;
- OPcache persistent classes must store the metadata;
- preload must not turn a restricted CE into a public CE;
- JIT must not replace a checked class fetch with an unchecked CE constant for
  restricted classes.
