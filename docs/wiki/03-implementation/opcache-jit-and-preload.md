# OPcache, JIT, and Preload

## Metadata Persistence

Namespace visibility metadata stored on `zend_class_entry` is persisted and
restored in:

- `ext/opcache/zend_persist.c`;
- `ext/opcache/zend_persist_calc.c`.

The file-cache path also serializes/deserializes
`zend_class_entry::namespace_visibility_namespace` in
`ext/opcache/zend_file_cache.c`. `ce_flags2` survives as part of the serialized
class-entry body.

Caller namespace metadata on `zend_op_array` is persisted for:

- `lexical_namespace`;
- `last_namespace_range`;
- `namespace_ranges` and each range's namespace string.

The optimizer must not replace a denied restricted class operation with a
precomputed result. Gate 4 adds an optimizer-side silent visibility check for
restricted CEs used by class-constant and static-method helpers. For top-level
op_arrays with namespace ranges, restricted CE optimization is conservative and
falls back to runtime VM checks.

## Preload

Preload resolves and links class dependencies ahead of requests. Namespace
visibility must behave the same with and without preload:

- preloaded restricted classes keep metadata;
- preload linking must enforce declaration-namespace access where class
  declarations extend/implement/use restricted declarations;
- request-time use from another namespace must still be checked;
- preload must not turn a restricted CE into a public one.

Relevant local files:

- `ext/opcache/ZendAccelerator.c`
- preload dependency resolution around lines 3845-3874;
- preload link path around line 4098;
- trait method preload fix paths around lines 4340-4391.

Gate 4 tests cover:

- preloaded restricted class/interface/trait/enum metadata;
- request-time denied semantic use from another namespace;
- already obtained object operations;
- allowed preload dependency linking;
- denied preload dependency linking for `extends`, `implements`, interface
  `extends`, and trait `use`.

## Inheritance Cache

OPcache hooks inheritance cache get/add in `ext/opcache/ZendAccelerator.c`.
Class-like visibility creates a risk: a class linked legally in one declaration
namespace might be reused in another context without rechecking access.

The implementation must prove either:

- inheritance cache entries are only reused for the same declaration and do not
  represent caller access, or
- access checks occur before cache reuse, or
- cache keys include all visibility-relevant context.

## JIT

JIT known-class and helper paths must not bypass visibility checks:

- `ext/opcache/jit/zend_jit.c` has `zend_get_known_class()` around line 567.
- It also performs trait lookup around line 737.
- `ext/opcache/jit/zend_jit_helpers.c` has `zend_jit_find_class_helper()`
  around line 188.

If JIT substitutes a known CE for a class fetch, it must either prove the CE is
unrestricted or preserve a runtime check for restricted CEs. This remains
explicitly deferred after Gate 4.

## Cache Invalidation

Restrictions are declaration metadata. OPcache invalidation should follow normal
script invalidation when the declaring file changes. Gate 4 tests compare:

- OPcache enabled;
- OPcache file-cache replay;
- preload where available;
- allowed-then-denied order;
- denied-then-allowed order.

JIT stays excluded from these tests by setting `opcache.jit=0`.
