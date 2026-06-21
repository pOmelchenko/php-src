# PHPT Plan

This plan targets revised RFC v1: `private(namespace)` exact access and
`protected(namespace)` subtree access.

## Implemented Prototype Tests

Implemented files in the current private/protected prototype:

- `Zend/tests/access_modifiers/ns_visibility_class_like_syntax.phpt`
- `Zend/tests/access_modifiers/ns_visibility_class_like_metadata.phpt`
- `Zend/tests/access_modifiers/ns_visibility_anonymous_class_error.phpt`
- `Zend/tests/access_modifiers/ns_visibility_duplicate_modifier_error.phpt`
- `Zend/tests/access_modifiers/ns_visibility_explicit_root_error.phpt`
- `ext/tokenizer/tests/ns_visibility_tokens.phpt`
- `Zend/tests/access_modifiers/ns_visibility_runtime_case_insensitive.phpt`
- `Zend/tests/access_modifiers/ns_visibility_runtime_new_static.phpt`
- `Zend/tests/access_modifiers/ns_visibility_runtime_new_dynamic.phpt`
- `Zend/tests/access_modifiers/ns_visibility_runtime_method_namespace.phpt`
- `Zend/tests/access_modifiers/ns_visibility_runtime_new_cache_order.phpt`
- `Zend/tests/access_modifiers/ns_visibility_runtime_segment_prefix.phpt`

Result on 2026-06-21 in the Docker debug build after private/protected
alignment: 12/12 passed. The Docker ZTS debug build was not rerun after this
alignment.

## Required v1 Parser Tests

| ID | Risk IDs | File | Status |
| --- | --- | --- | --- |
| PHPT-SYN-001 | RISK-002 | `ns_visibility_private_namespace_class_like.phpt` | Planned |
| PHPT-SYN-002 | RISK-001 | `ns_visibility_class_like_syntax.phpt` | Existing |
| PHPT-SYN-003 | RISK-002 | `ns_visibility_plain_private_class_not_v1.phpt` | Planned |
| PHPT-SYN-004 | RISK-017 | `ns_visibility_internal_class_not_v1.phpt` | Planned |
| PHPT-SYN-005 | RISK-006 | `ns_visibility_explicit_root_error.phpt` | Existing |
| PHPT-SYN-006 | RISK-003 | `ns_visibility_anonymous_class_rejected.phpt` | Existing/prototype |

## Required Exact Namespace Tests

| ID | Risk IDs | File | Status |
| --- | --- | --- | --- |
| PHPT-EXACT-001 | RISK-004 | `ns_visibility_same_namespace_same_file.phpt` | Planned |
| PHPT-EXACT-002 | RISK-004 | `ns_visibility_same_namespace_other_file.phpt` | Planned |
| PHPT-EXACT-003 | RISK-006 | `ns_visibility_runtime_new_static.phpt` | Existing partial |
| PHPT-EXACT-004 | RISK-006 | `ns_visibility_runtime_new_static.phpt` | Existing partial |
| PHPT-EXACT-005 | RISK-006 | `ns_visibility_runtime_new_static.phpt` | Existing partial |
| PHPT-EXACT-006 | RISK-006 | `ns_visibility_runtime_segment_prefix.phpt` | Existing |
| PHPT-EXACT-007 | RISK-013 | `ns_visibility_global_global.phpt` | Planned |
| PHPT-EXACT-008 | RISK-013 | `ns_visibility_global_named_denied.phpt` | Planned |
| PHPT-EXACT-009 | RISK-013 | `ns_visibility_named_global_denied.phpt` | Planned |
| PHPT-EXACT-010 | RISK-004 | `ns_visibility_runtime_case_insensitive.phpt` | Existing |
| PHPT-EXACT-011 | RISK-004 | `ns_visibility_bracketed_namespace.phpt` | Planned |
| PHPT-EXACT-012 | RISK-004 | `ns_visibility_multiple_namespace_blocks.phpt` | Planned |

## Required Protected Subtree Tests

| ID | Risk IDs | File | Status |
| --- | --- | --- | --- |
| PHPT-PROT-001 | RISK-006 | `ns_visibility_runtime_new_static.phpt` | Existing partial |
| PHPT-PROT-002 | RISK-006 | `ns_visibility_runtime_new_dynamic.phpt` | Existing partial |
| PHPT-PROT-003 | RISK-006 | `ns_visibility_runtime_segment_prefix.phpt` | Existing |
| PHPT-PROT-004 | RISK-006 | `ns_visibility_protected_explicit_root_future.phpt` | Planned if root stays future |

## Required Lexical Tests

| ID | Risk IDs | File | Status |
| --- | --- | --- | --- |
| PHPT-LEX-001 | RISK-004 | `ns_visibility_function_lexical_namespace.phpt` | Planned |
| PHPT-LEX-002 | RISK-004 | `ns_visibility_method_lexical_namespace.phpt` | Existing/prototype partial |
| PHPT-LEX-003 | RISK-004 | `ns_visibility_closure_namespace.phpt` | Planned |
| PHPT-LEX-004 | RISK-004 | `ns_visibility_arrow_function_namespace.phpt` | Planned |
| PHPT-LEX-005 | RISK-004 | `ns_visibility_closure_bind_does_not_change_namespace.phpt` | Planned |
| PHPT-LEX-006 | RISK-004 | `ns_visibility_eval_global_default.phpt` | Planned |
| PHPT-LEX-007 | RISK-004 | `ns_visibility_eval_explicit_namespace.phpt` | Planned |
| PHPT-LEX-008 | RISK-012 | `ns_visibility_trait_body_declaration_namespace.phpt` | Planned |
| PHPT-LEX-009 | RISK-012 | `ns_visibility_trait_body_no_using_class_gain.phpt` | Planned |
| PHPT-LEX-010 | RISK-012 | `ns_visibility_trait_use_consuming_namespace.phpt` | Planned |
| PHPT-LEX-011 | RISK-004 | `ns_visibility_inherited_method_body_namespace.phpt` | Planned |

## Required Operation Tests

| ID | Risk IDs | File | Status |
| --- | --- | --- | --- |
| PHPT-OP-001 | RISK-004 | `ns_visibility_new_static.phpt` | Existing/prototype partial |
| PHPT-OP-002 | RISK-004 | `ns_visibility_new_dynamic.phpt` | Existing/prototype partial |
| PHPT-OP-003 | RISK-004 | `ns_visibility_static_method.phpt` | Planned |
| PHPT-OP-004 | RISK-004 | `ns_visibility_static_property.phpt` | Planned |
| PHPT-OP-005 | RISK-004 | `ns_visibility_class_constant.phpt` | Planned |
| PHPT-OP-006 | RISK-014 | `ns_visibility_class_name_constant.phpt` | Planned |
| PHPT-OP-007 | RISK-004 | `ns_visibility_extends.phpt` | Planned |
| PHPT-OP-008 | RISK-004 | `ns_visibility_implements.phpt` | Planned |
| PHPT-OP-009 | RISK-004 | `ns_visibility_interface_extends.phpt` | Planned |
| PHPT-OP-010 | RISK-012 | `ns_visibility_trait_use.phpt` | Planned |
| PHPT-OP-011 | RISK-014 | `ns_visibility_instanceof.phpt` | Planned |
| PHPT-OP-012 | RISK-014 | `ns_visibility_catch.phpt` | Planned |
| PHPT-OP-013 | RISK-011 | `ns_visibility_param_type.phpt` | Planned |
| PHPT-OP-014 | RISK-011 | `ns_visibility_return_type.phpt` | Planned |
| PHPT-OP-015 | RISK-011 | `ns_visibility_property_type.phpt` | Planned |
| PHPT-OP-016 | RISK-011 | `ns_visibility_class_const_type.phpt` | Planned |
| PHPT-OP-017 | RISK-011 | `ns_visibility_promoted_property.phpt` | Planned |
| PHPT-OP-018 | RISK-011 | `ns_visibility_union_type.phpt` | Planned |
| PHPT-OP-019 | RISK-011 | `ns_visibility_intersection_type.phpt` | Planned |
| PHPT-OP-020 | RISK-011 | `ns_visibility_dnf_type.phpt` | Planned |
| PHPT-OP-021 | RISK-004 | `ns_visibility_attribute_class.phpt` | Planned |
| PHPT-OP-022 | RISK-014 | `ns_visibility_attribute_class_string.phpt` | Planned |
| PHPT-OP-023 | RISK-004 | `ns_visibility_first_class_callable.phpt` | Planned |
| PHPT-OP-024 | RISK-004 | `ns_visibility_string_callable.phpt` | Planned |
| PHPT-OP-025 | RISK-004 | `ns_visibility_array_callable.phpt` | Planned |
| PHPT-OP-026 | RISK-004 | `ns_visibility_closure_from_callable.phpt` | Planned |
| PHPT-OP-027 | RISK-004 | `ns_visibility_call_user_func.phpt` | Planned |
| PHPT-OP-028 | RISK-004 | `ns_visibility_is_callable.phpt` | Planned |
| PHPT-OP-029 | RISK-014 | `ns_visibility_class_exists_family.phpt` | Planned |
| PHPT-OP-030 | RISK-014 | `ns_visibility_is_a_is_subclass_of.phpt` | Planned |
| PHPT-OP-031 | RISK-014 | `ns_visibility_method_property_exists.phpt` | Planned |
| PHPT-OP-032 | RISK-015 | `ns_visibility_class_alias.phpt` | Planned |
| PHPT-OP-033 | RISK-010 | `ns_visibility_reflection_metadata.phpt` | Existing/prototype partial |
| PHPT-OP-034 | RISK-010 | `ns_visibility_reflection_new_instance.phpt` | Planned |
| PHPT-OP-035 | RISK-010 | `ns_visibility_reflection_new_without_ctor.phpt` | Planned |
| PHPT-OP-036 | RISK-004 | `ns_visibility_serialize_existing_object.phpt` | Planned |
| PHPT-OP-037 | RISK-004 | `ns_visibility_unserialize_restricted.phpt` | Planned |
| PHPT-OP-038 | RISK-004 | `ns_visibility_clone_existing_object.phpt` | Planned |
| PHPT-OP-039 | RISK-004 | `ns_visibility_set_state.phpt` | Planned |
| PHPT-OP-040 | RISK-008 | `ns_visibility_direct_require_no_bypass.phpt` | Planned |

## Cache, OPcache, and Preload Tests

| ID | Risk IDs | File | Status |
| --- | --- | --- | --- |
| PHPT-CACHE-001 | RISK-008 | `ns_visibility_allowed_then_denied_cache.phpt` | Existing/prototype for `new`; broaden |
| PHPT-CACHE-002 | RISK-008 | `ns_visibility_denied_then_allowed_cache.phpt` | Existing/prototype for `new`; broaden |
| PHPT-CACHE-003 | RISK-008 | `ns_visibility_two_op_arrays_cache.phpt` | Planned |
| PHPT-CACHE-004 | RISK-015 | `ns_visibility_alias_allowed_then_denied.phpt` | Planned |
| PHPT-CACHE-005 | RISK-015 | `ns_visibility_alias_denied_then_allowed.phpt` | Planned |
| PHPT-CACHE-006 | RISK-008 | `ns_visibility_opcache_off.phpt` | Planned |
| PHPT-CACHE-007 | RISK-008 | `ns_visibility_opcache_on.phpt` | Planned |
| PHPT-CACHE-008 | RISK-008 | `ns_visibility_preload.phpt` | Planned |
| PHPT-CACHE-009 | RISK-008 | ZTS targeted run | Environment-dependent |

## Commands and Current Results

Last executed after private/protected alignment:

```sh
sapi/cli/php run-tests.php -q \
  Zend/tests/access_modifiers/ns_visibility_class_like_metadata.phpt \
  Zend/tests/access_modifiers/ns_visibility_class_like_syntax.phpt \
  Zend/tests/access_modifiers/ns_visibility_duplicate_modifier_error.phpt \
  Zend/tests/access_modifiers/ns_visibility_anonymous_class_error.phpt \
  Zend/tests/access_modifiers/ns_visibility_explicit_root_error.phpt \
  Zend/tests/access_modifiers/ns_visibility_runtime_case_insensitive.phpt \
  Zend/tests/access_modifiers/ns_visibility_runtime_new_static.phpt \
  Zend/tests/access_modifiers/ns_visibility_runtime_new_dynamic.phpt \
  Zend/tests/access_modifiers/ns_visibility_runtime_method_namespace.phpt \
  Zend/tests/access_modifiers/ns_visibility_runtime_segment_prefix.phpt \
  Zend/tests/access_modifiers/ns_visibility_runtime_new_cache_order.phpt \
  ext/tokenizer/tests/ns_visibility_tokens.phpt
```

Current result: 12/12 passed in the Docker debug build.

Not run in Phase 2:

- remaining v1 tests outside the implemented prototype slice;
- full `make test`;
- OPcache on/off tests;
- preload tests;
- JIT tests;
- benchmark tests.

Reason: this implementation pass covered parser/tokenizer metadata and a narrow
runtime `new`/class-fetch slice, not the full operation matrix.
