# Access Matrix

Notation:

- "Rule" means allowed if the caller namespace satisfies the target class-like
  visibility rule, denied otherwise.
- "Caller" means lexical caller namespace.
- "CE" means `zend_class_entry`.
- "Autoload" means whether the operation may need the target class to be loaded
  before metadata is known.
- Runtime violations should prefer `Error` unless the operation is already a
  compile/link-time fatal error path.

## Object Construction and Static Access

| Operation | Rule | Stage | Caller | Autoload | Expected error | PHPT |
| --- | --- | --- | --- | --- | --- | --- |
| `new ClassName()` | Check target class name before construction | runtime class fetch after compile-time name resolution | namespace of `new` expression | yes | `Error` | `ns_visibility_new_static.phpt` |
| `new $className()` | Check resolved CE | runtime | namespace of `new` expression | yes | `Error` | `ns_visibility_new_dynamic.phpt` |
| `ClassName::method()` | Check target class name, then existing method visibility | runtime static call resolution | namespace of static call | yes | `Error` | `ns_visibility_static_method.phpt` |
| `ClassName::$property` | Check target class name, then existing property visibility | runtime static property fetch | namespace of property fetch | yes | `Error` | `ns_visibility_static_property.phpt` |
| `ClassName::CONSTANT` | Check target class name, then class constant visibility | runtime constant fetch | namespace of constant fetch | yes | `Error` | `ns_visibility_class_constant.phpt` |
| `ClassName::class` | Unresolved: normally compiles to string without autoload | compile-time constant expression | namespace of expression if checked | no today | unresolved | `ns_visibility_class_name_constant.phpt` |

## Callables

| Operation | Rule | Stage | Caller | Autoload | Expected error | PHPT |
| --- | --- | --- | --- | --- | --- | --- |
| `ClassName::method(...)` first-class callable | Check when resolving callable target | runtime | namespace of expression | yes | `Error` | `ns_visibility_first_class_callable.phpt` |
| `'ClassName::method'` string callable | Check when resolved or invoked | runtime | lexical caller of resolver/invoker | yes | `Error` or `false` for probes | `ns_visibility_string_callable.phpt` |
| `[ClassName::class, 'method']` array callable | Check class string when resolving callable if CE is needed | runtime | namespace of resolver/invoker | yes if string resolved | `Error` or `false` for probes | `ns_visibility_array_callable.phpt` |
| `Closure::fromCallable()` | Check callable target | runtime | namespace of call expression | yes | `Error` | `ns_visibility_closure_from_callable.phpt` |
| `call_user_func()` | Check callable target before invocation | runtime internal function using caller op array | lexical namespace of call site | yes | `Error` | `ns_visibility_call_user_func.phpt` |
| `is_callable()` | Should not grant access; may return `false` for inaccessible target | runtime probe | namespace of call site | optional/current behavior dependent | `false` preferred over throwing | `ns_visibility_is_callable.phpt` |

## Inheritance and Type Relationships

| Operation | Rule | Stage | Caller | Autoload | Expected error | PHPT |
| --- | --- | --- | --- | --- | --- | --- |
| `class Child extends Parent` | Check `Parent` from `Child` declaration namespace | class linking | namespace of `Child` declaration | yes | compile/link fatal or `Error` | `ns_visibility_extends.phpt` |
| `class C implements I` | Check `I` from `C` declaration namespace | class linking | namespace of `C` declaration | yes | compile/link fatal or `Error` | `ns_visibility_implements.phpt` |
| `interface B extends A` | Check `A` from `B` declaration namespace | class linking | namespace of `B` declaration | yes | compile/link fatal or `Error` | `ns_visibility_interface_extends.phpt` |
| `use TraitName` in class | Check trait CE from using class namespace | class linking / trait binding | namespace of using class declaration | yes | compile/link fatal or `Error` | `ns_visibility_trait_use.phpt` |
| `instanceof ClassName` | Check RHS class name use | runtime | namespace of `instanceof` expression | no autoload for literal today | `Error` if loaded and inaccessible; unresolved if unloaded | `ns_visibility_instanceof.phpt` |
| `catch (ClassName $e)` | Check catch class name use | runtime catch matching / compile metadata | namespace of `catch` | no autoload in current handler | `Error` or skipped catch unresolved | `ns_visibility_catch.phpt` |

## Type Positions

| Operation | Rule | Stage | Caller | Autoload | Expected error | PHPT |
| --- | --- | --- | --- | --- | --- | --- |
| Parameter type | Public or private signature may name restricted type only if caller/declaration rule allows; consistent accessibility unresolved | compile/type resolution/class linking/runtime verification | namespace of declaring function/method | may be lazy | compile/link fatal or `Error` | `ns_visibility_param_type.phpt` |
| Return type | Same as parameter type | compile/type resolution/class linking/runtime verification | namespace of declaring function/method | may be lazy | compile/link fatal or `TypeError` path plus access error unresolved | `ns_visibility_return_type.phpt` |
| Property type | Check type name use from declaring class namespace | class compile/link/type resolution | namespace of declaring class | may be lazy | compile/link fatal or `Error` | `ns_visibility_property_type.phpt` |
| Typed class constant | Check type name use from declaring class namespace | class compile/link/type resolution | namespace of declaring class | may be lazy | compile/link fatal or `Error` | `ns_visibility_class_const_type.phpt` |
| Union types | Check each class-like component | type resolution | declaration namespace | may be lazy | compile/link fatal or `Error` | `ns_visibility_union_type.phpt` |
| Intersection types | Check each class-like component | type resolution | declaration namespace | may be lazy | compile/link fatal or `Error` | `ns_visibility_intersection_type.phpt` |
| DNF types | Check each class-like component | type resolution | declaration namespace | may be lazy | compile/link fatal or `Error` | `ns_visibility_dnf_type.phpt` |
| Constructor property promotion | Check promoted property type from class declaration namespace | class compile/link/type resolution | namespace of declaring class | may be lazy | compile/link fatal or `Error` | `ns_visibility_promoted_property.phpt` |
| Attribute class | Attribute object creation checks the attribute class from the attributed declaration namespace; `::class` arguments remain strings until later semantic use | reflection runtime | namespace of attributed declaration | attribute class autoload follows existing ReflectionAttribute behavior | `Error` from `ReflectionAttribute::newInstance()` | `ns_visibility_attribute_new_instance.phpt` |

## Existence and Introspection Functions

| Operation | Rule | Stage | Caller | Autoload | Expected error | PHPT |
| --- | --- | --- | --- | --- | --- | --- |
| `class_exists()` | Proposed: may reveal existence; does not grant use | runtime | namespace of call site if access-aware variant is chosen | optional argument controls autoload | `true`/`false`, no access error preferred | `ns_visibility_class_exists.phpt` |
| `interface_exists()` | Same as `class_exists()` | runtime | call site | optional | no access error preferred | `ns_visibility_interface_exists.phpt` |
| `trait_exists()` | Same as `class_exists()` | runtime | call site | optional | no access error preferred | `ns_visibility_trait_exists.phpt` |
| `enum_exists()` | Same as `class_exists()` | runtime | call site | optional | no access error preferred | `ns_visibility_enum_exists.phpt` |
| `is_a()` | If it resolves a class-string target, access semantics must be decided | runtime | call site | optional flag | `false` preferred for inaccessible probes | `ns_visibility_is_a.phpt` |
| `is_subclass_of()` | Same as `is_a()` | runtime | call site | optional flag | `false` preferred for inaccessible probes | `ns_visibility_is_subclass_of.phpt` |
| `method_exists()` | Existing probe may reveal methods; should not grant class use | runtime | call site | yes for class string | no access error preferred unless class resolution is classified as use | `ns_visibility_method_exists.phpt` |
| `property_exists()` | Same as `method_exists()` | runtime | call site | yes for class string | no access error preferred unless classified as use | `ns_visibility_property_exists.phpt` |
| `defined('C::X')` | Class constant string may resolve class name | runtime | call site | current behavior dependent | unresolved | `ns_visibility_defined_class_const.phpt` |
| `class_alias()` | Alias does not remove restriction; metadata belongs to CE | runtime | namespace of alias call for source class resolution | yes | `Error` if source access is enforced here | `ns_visibility_class_alias.phpt` |

## Reflection

| Operation | Rule | Stage | Caller | Autoload | Expected error | PHPT |
| --- | --- | --- | --- | --- | --- | --- |
| `new ReflectionClass('Restricted')` | Proposed: metadata readable; construction of reflection may be allowed | runtime reflection constructor | namespace of call site if enforced | yes | unresolved; likely no access error | `ns_visibility_reflection_construct.phpt` |
| `ReflectionClass::newInstance()` | Should enforce class construction visibility unless RFC chooses privileged bypass | runtime | namespace of reflection call site or stored creator context | no, CE already known | `Error` | `ns_visibility_reflection_new_instance.phpt` |
| `ReflectionClass::newInstanceWithoutConstructor()` | Same as `newInstance()` | runtime | call site/stored context | no | `Error` | `ns_visibility_reflection_new_without_ctor.phpt` |
| `ReflectionMethod::invoke()` | Name model: if object already obtained, method member visibility applies; class-like visibility should not membrane every call | runtime | call site | no | existing reflection rules | `ns_visibility_reflection_method_invoke.phpt` |

## Serialization, Objects, and Loading

| Operation | Rule | Stage | Caller | Autoload | Expected error | PHPT |
| --- | --- | --- | --- | --- | --- | --- |
| `serialize()` existing object | Name model allows existing object operation | runtime | no target name use | no | no access error | `ns_visibility_serialize.phpt` |
| `unserialize()` restricted class payload | Name-based object construction should enforce | runtime unserialize class lookup | namespace of call site or internal privileged context unresolved | yes | `Error` or incomplete class behavior unresolved | `ns_visibility_unserialize.phpt` |
| `__set_state()` via `var_export()`/`eval()` | Class name use should enforce at eval/compile/runtime | eval/runtime | eval lexical namespace | yes | `Error` | `ns_visibility_set_state.phpt` |
| Cloning already obtained object | Name model allows | runtime object clone | no target name use | no | no access error | `ns_visibility_clone_existing.phpt` |
| Preload | Same behavior as non-preload; metadata persisted | preload/link/runtime | declaring or accessing script namespace | yes during preload | preload fatal or runtime `Error` | `ns_visibility_preload.phpt` |
| OPcache | Same behavior with and without OPcache | compile cache/runtime | original lexical caller | cache dependent | same as no OPcache | `ns_visibility_opcache.phpt` |
| Allowed then denied | First allowed load must not bypass later denied access | runtime cache/class table | second caller namespace | no after first load | `Error` | `ns_visibility_allowed_then_denied.phpt` |
| Denied then allowed | First denied use must not poison later allowed use | runtime cache/class table | second caller namespace | yes/no | allowed | `ns_visibility_denied_then_allowed.phpt` |
| Direct `require` of class file | Declaration loading does not grant later access | compile/include/runtime | require caller for include, later use caller for use | no if file directly included | later `Error` | `ns_visibility_direct_require.phpt` |
| Autoloader side effects | Autoloader may run before denial because metadata is known only after load | runtime class fetch | original use namespace, not autoloader namespace | yes | `Error` after load | `ns_visibility_autoload_side_effects.phpt` |
