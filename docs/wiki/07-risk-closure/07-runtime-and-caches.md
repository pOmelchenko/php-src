# Runtime and Caches

The implementation must find every path that produces or reuses a
`zend_class_entry`. The check cannot rely on one opcode.

## Class-entry Sources

| Path | File/function | Operation categories | Autoload behavior | Cache | Check requirement |
| --- | --- | --- | --- | --- | --- |
| General lookup | `Zend/zend_execute_API.c:zend_lookup_class_ex()` | Many probes and semantic operations | Yes unless `NO_AUTOLOAD` or compiling | CE cache on strings and class table | Do not check blindly; callers include probes. Semantic callers must check after return, including cache hits |
| General fetch | `zend_fetch_class()`, `zend_fetch_class_by_name()` | `new`, static access, dynamic names | Yes depending flags | May use CE cache via lookup | Check in callers with an explicit caller namespace |
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
| `ZEND_NEW` | `new C`, `new $class`, cached `new` | Yes; rechecks cache hits | Existing Phase C `new` tests | No | GATE 3 IMPLEMENTED |
| `ZEND_FETCH_CLASS` | Dynamic class fetch and some static prep | Yes; VM handlers pass current lexical namespace explicitly | Existing dynamic tests | No | GATE 3 IMPLEMENTED |
| Static handlers | static method/property/constant | Yes; constant folding and OPcache optimizer shortcuts refuse inaccessible restricted constants/classes | `ns_visibility_gate3_operations.phpt` | `ns_visibility_opcache_cli.phpt`, `ns_visibility_opcache_file_cache.phpt`, `ns_visibility_preload.phpt` | GATE 4 IMPLEMENTED |
| Inheritance/linking | `extends`, `implements`, trait `use` | Yes for runtime link, early-bind fallback, inheritance cache reuse, and preload linking | `ns_visibility_gate3_operations.phpt` | `ns_visibility_opcache_cli.phpt`, `ns_visibility_preload_linking.phpt` | GATE 4 IMPLEMENTED |
| Type resolution | parameter, return, property, promoted property, class constant, union/intersection/DNF | Yes when resolved to CE | `ns_visibility_gate3_type_positions.phpt` | Planned | GATE 3 IMPLEMENTED |
| `instanceof`/`catch` | runtime type checks | Yes; `catch` reports access without throwing on top of active exception | `ns_visibility_gate3_operations.phpt` | Planned | GATE 3 IMPLEMENTED |
| Callables | first-class/string/array/call_user_func | Yes for class-string validation and invocation; object callables remain object/member semantics | `ns_visibility_gate3_callables_reflection_serialization.phpt` | Planned | GATE 3 IMPLEMENTED |
| Existence probes | class/interface/trait/enum/method/property exists | No denial by design | Planned probe tests | Planned for cache behavior | RESOLVED-POLICY |
| Reflection | metadata and construction | Metadata allowed; ReflectionClass instantiation checked | `ns_visibility_gate3_callables_reflection_serialization.phpt` | Planned | GATE 3 IMPLEMENTED |
| Aliases | `class_alias()` | Metadata belongs to CE; alias use still checks | `ns_visibility_gate3_callables_reflection_serialization.phpt` | Planned | GATE 3 IMPLEMENTED |
| Unserialize | payload class names | Yes before object allocation | `ns_visibility_gate3_callables_reflection_serialization.phpt` | Planned | GATE 3 IMPLEMENTED |
| OPcache/preload | optimized/persistent CE | Shared-memory and file-cache metadata persisted; optimizer restricted-CE shortcuts guarded; preload metadata/linking validated | Gate 3 base tests | `ns_visibility_opcache_cli.phpt`, `ns_visibility_opcache_namespace_ranges.phpt`, `ns_visibility_opcache_file_cache.phpt`, `ns_visibility_preload.phpt`, `ns_visibility_preload_linking.phpt` | GATE 4 IMPLEMENTED |
| JIT | optimized class fetch/static paths | Yes; known restricted CEs are checked or rejected for compile-time substitution, and the runtime class helper checks before returning CE | `ns_visibility_jit_function.phpt`, `ns_visibility_jit_tracing.phpt`, `ns_visibility_jit_namespace_ranges.phpt`, `ns_visibility_jit_inheritance_linking.phpt` | Same files | GATE 4+ IMPLEMENTED |

`GATE 4 IMPLEMENTED` means the C prototype has targeted PHPT coverage for the
semantic path with OPcache/preload enabled. `GATE 4+ IMPLEMENTED` additionally
covers JIT correctness. Neither status claims benchmark completion.

## Acceptance Criterion

> Class entry, разрешённый и закешированный одним namespace, не должен
> становиться доступным другому namespace без повторной проверки.

Therefore:

- opcode runtime cache hits must run the access check again;
- CE cache hits from `zend_lookup_class_ex()` must be followed by access checks
  in semantic callers;
- class-table alias entries must point to the same restricted CE metadata;
- OPcache persistent and file-cache classes must store the metadata;
- OPcache optimizer shortcuts must not turn denied restricted CE use into a
  precomputed success;
- preload must not turn a restricted CE into a public CE;
- JIT must not replace a checked class fetch with an unchecked CE constant for
  restricted classes.
