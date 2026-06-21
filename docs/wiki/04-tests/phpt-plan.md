# PHPT Plan

Phase B parser/metadata PHPT files exist. Phase C now has a minimal runtime
`new` enforcement test set, but the broader operation matrix remains pending.

## Phase B Parser and Metadata Tests

Directory:

- `Zend/tests/access_modifiers`

Implemented files:

- `Zend/tests/access_modifiers/ns_visibility_class_like_syntax.phpt`
- `Zend/tests/access_modifiers/ns_visibility_class_like_metadata.phpt`
- `Zend/tests/access_modifiers/ns_visibility_anonymous_class_error.phpt`
- `Zend/tests/access_modifiers/ns_visibility_duplicate_modifier_error.phpt`
- `Zend/tests/access_modifiers/ns_visibility_explicit_root_error.phpt`
- `ext/tokenizer/tests/ns_visibility_tokens.phpt`

Still planned:

- `ns_visibility_private_and_protected_rejected.phpt`;
- parser rejection for `internal class A {}` if needed;
- parser-order tests if the final syntax accepts both modifier orders.

Assertions:

- accepted syntax parses;
- rejected syntax has stable parse/compile error;
- metadata can be observed by temporary debug/reflection surface;
- no runtime enforcement is claimed if Phase B does not implement it.

## Phase C Core Runtime Tests

Implemented files:

- `Zend/tests/access_modifiers/ns_visibility_runtime_new_static.phpt`
- `Zend/tests/access_modifiers/ns_visibility_runtime_new_dynamic.phpt`
- `Zend/tests/access_modifiers/ns_visibility_runtime_method_namespace.phpt`
- `Zend/tests/access_modifiers/ns_visibility_runtime_segment_prefix.phpt`
- `Zend/tests/access_modifiers/ns_visibility_runtime_new_cache_order.phpt`

Still planned:

- `ns_visibility_private_same_namespace_allowed.phpt`
- `ns_visibility_private_child_namespace_denied.phpt`
- `ns_visibility_protected_same_namespace_allowed.phpt`
- `ns_visibility_protected_child_namespace_allowed.phpt`
- `ns_visibility_protected_sibling_namespace_denied.phpt`
- `ns_visibility_segment_prefix_false_positive.phpt`
- `ns_visibility_global_private.phpt`
- `ns_visibility_global_protected.phpt`
- `ns_visibility_case_handling.phpt`
- `ns_visibility_error_message.phpt`

Assertions:

- exact `Error` type for runtime denials;
- stable message without absolute paths;
- caller namespace included;
- target class name included;
- allowed scope included where practical.

Current limitation:

- tests call `new` from named functions so the Phase C prototype can derive
  caller namespace from function/method metadata;
- top-level, closure, eval, and trait caller tests are still pending.

## Phase D Operation Coverage Tests

Planned files:

- `ns_visibility_new_static.phpt`
- `ns_visibility_new_dynamic.phpt`
- `ns_visibility_static_method.phpt`
- `ns_visibility_static_property.phpt`
- `ns_visibility_class_constant.phpt`
- `ns_visibility_class_name_constant.phpt`
- `ns_visibility_first_class_callable.phpt`
- `ns_visibility_string_callable.phpt`
- `ns_visibility_array_callable.phpt`
- `ns_visibility_closure_from_callable.phpt`
- `ns_visibility_call_user_func.phpt`
- `ns_visibility_is_callable.phpt`
- `ns_visibility_extends.phpt`
- `ns_visibility_implements.phpt`
- `ns_visibility_interface_extends.phpt`
- `ns_visibility_trait_use.phpt`
- `ns_visibility_instanceof.phpt`
- `ns_visibility_catch.phpt`
- `ns_visibility_param_type.phpt`
- `ns_visibility_return_type.phpt`
- `ns_visibility_property_type.phpt`
- `ns_visibility_class_const_type.phpt`
- `ns_visibility_union_type.phpt`
- `ns_visibility_intersection_type.phpt`
- `ns_visibility_dnf_type.phpt`
- `ns_visibility_promoted_property.phpt`
- `ns_visibility_attribute_class_arg.phpt`
- `ns_visibility_class_exists.phpt`
- `ns_visibility_interface_exists.phpt`
- `ns_visibility_trait_exists.phpt`
- `ns_visibility_enum_exists.phpt`
- `ns_visibility_is_a.phpt`
- `ns_visibility_is_subclass_of.phpt`
- `ns_visibility_method_exists.phpt`
- `ns_visibility_property_exists.phpt`
- `ns_visibility_defined_class_const.phpt`
- `ns_visibility_class_alias.phpt`
- `ns_visibility_reflection_construct.phpt`
- `ns_visibility_reflection_new_instance.phpt`
- `ns_visibility_reflection_new_without_ctor.phpt`
- `ns_visibility_reflection_method_invoke.phpt`
- `ns_visibility_serialize.phpt`
- `ns_visibility_unserialize.phpt`
- `ns_visibility_set_state.phpt`
- `ns_visibility_clone_existing.phpt`
- `ns_visibility_direct_require.phpt`
- `ns_visibility_autoload_side_effects.phpt`

## Lexical Caller Tests

Planned files:

- `ns_visibility_function_lexical_namespace.phpt`
- `ns_visibility_method_lexical_namespace.phpt`
- `ns_visibility_static_method_lexical_namespace.phpt`
- `ns_visibility_closure_namespace.phpt`
- `ns_visibility_arrow_function_namespace.phpt`
- `ns_visibility_closure_bind_does_not_change_namespace.phpt`
- `ns_visibility_eval_explicit_namespace.phpt`
- `ns_visibility_eval_inherited_namespace.phpt`
- `ns_visibility_multiple_namespace_blocks.phpt`
- `ns_visibility_bracketed_namespace.phpt`
- `ns_visibility_unbracketed_namespace.phpt`
- `ns_visibility_trait_decl_namespace_model.phpt`
- `ns_visibility_trait_using_class_namespace_model.phpt`

## Cache and OPcache Tests

Planned files:

- `ns_visibility_allowed_then_denied_cache.phpt`
- `ns_visibility_denied_then_allowed_cache.phpt`
- `ns_visibility_alias_allowed_then_denied.phpt`
- `ns_visibility_opcache_on.phpt`
- `ns_visibility_opcache_off.phpt`
- `ns_visibility_preload.phpt`
- `ns_visibility_jit.phpt`

OPcache/JIT tests should be skipped when the extension or mode is unavailable.

## Commands and Results

Docker debug build plus targeted PHPT run:

```sh
sapi/cli/php run-tests.php -q \
  Zend/tests/access_modifiers/ns_visibility_class_like_metadata.phpt \
  Zend/tests/access_modifiers/ns_visibility_class_like_syntax.phpt \
  Zend/tests/access_modifiers/ns_visibility_duplicate_modifier_error.phpt \
  Zend/tests/access_modifiers/ns_visibility_anonymous_class_error.phpt \
  Zend/tests/access_modifiers/ns_visibility_explicit_root_error.phpt \
  Zend/tests/access_modifiers/ns_visibility_runtime_new_static.phpt \
  Zend/tests/access_modifiers/ns_visibility_runtime_new_dynamic.phpt \
  Zend/tests/access_modifiers/ns_visibility_runtime_method_namespace.phpt \
  Zend/tests/access_modifiers/ns_visibility_runtime_segment_prefix.phpt \
  Zend/tests/access_modifiers/ns_visibility_runtime_new_cache_order.phpt \
  ext/tokenizer/tests/ns_visibility_tokens.phpt
```

Result on 2026-06-21: 11/11 passed.

Also run:

- Docker debug build: passed;
- Docker ZTS debug build: passed.

Not run yet:

- full `make test`;
- OPcache tests;
- JIT tests.

Reason: Phase C does not yet cover the optimized paths that OPcache/JIT tests
need to exercise.
