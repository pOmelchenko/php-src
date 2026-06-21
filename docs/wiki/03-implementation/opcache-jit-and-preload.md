# OPcache, JIT, and Preload

## Metadata Persistence

If namespace visibility metadata is stored on `zend_class_entry`, OPcache must
persist and restore it in:

- `ext/opcache/zend_persist.c`;
- `ext/opcache/zend_persist_calc.c`.

Likely additions:

- count and persist declaration namespace string;
- count and persist effective visibility root string if explicit root is later
  supported;
- ensure strings are interned consistently;
- ensure `class_alias()` sharing of the same CE preserves metadata.

If caller namespace is stored on `zend_op_array`, the same persistence work is
needed for op arrays. PR #20421 is useful prior art here.

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
unrestricted or preserve a runtime check for restricted CEs.

## Cache Invalidation

Restrictions are declaration metadata. OPcache invalidation should follow normal
script invalidation when the declaring file changes. Tests must compare:

- OPcache disabled;
- OPcache enabled;
- preload where available;
- allowed-then-denied order;
- denied-then-allowed order.

