# PHPT Plan

No PHPT files were created in this iteration because no engine behavior was
implemented. The first actual tests should be added with Phase B parser and
metadata work.

## Phase B Parser and Metadata Tests

Directory:

- `Zend/tests/access_modifiers`

Planned files:

- `ns_visibility_class_private_syntax.phpt`
- `ns_visibility_class_protected_syntax.phpt`
- `ns_visibility_interface_syntax.phpt`
- `ns_visibility_trait_syntax.phpt`
- `ns_visibility_enum_syntax.phpt`
- `ns_visibility_anonymous_class_rejected.phpt`
- `ns_visibility_duplicate_modifier_rejected.phpt`
- `ns_visibility_private_and_protected_rejected.phpt`
- `ns_visibility_explicit_root_rejected_before_phase_f.phpt`
- `ns_visibility_reflection_metadata.phpt`
- `ns_visibility_tokenizer.phpt`

Assertions:

- accepted syntax parses;
- rejected syntax has stable parse/compile error;
- metadata can be observed by temporary debug/reflection surface;
- no runtime enforcement is claimed if Phase B does not implement it.

## Phase C Core Runtime Tests

Planned files:

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

## Commands

Once a build exists:

```sh
TEST_PHP_ARGS="-q" make test TESTS="Zend/tests/access_modifiers/ns_visibility_*.phpt"
TEST_PHP_ARGS="-q -d opcache.enable_cli=1" make test TESTS="Zend/tests/access_modifiers/ns_visibility_opcache_*.phpt"
```

Not run in this iteration:

- targeted PHPT tests;
- full `make test`;
- debug build;
- ZTS build;
- OPcache tests;
- JIT tests.

Reason: the repository is not configured, no CLI binary is present, `re2c` is
missing, and Bison is old.

