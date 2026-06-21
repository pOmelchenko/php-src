# Runtime Enforcement

## Central Check

The implementation should avoid scattering incompatible policy logic across
each opcode. A central function should own the rule, but caller context must be
passed explicitly:

```c
bool zend_check_class_namespace_visibility_from(
    const zend_class_entry *ce,
    const zend_string *caller_namespace,
    zend_class_namespace_visibility_failure_mode failure_mode
);
```

There is intentionally no `zend_check_class_namespace_visibility(ce)` wrapper
that derives caller context internally. VM handlers and runtime helpers that use
the current frame must still spell that out with
`zend_get_current_lexical_namespace()` so non-VM paths do not accidentally reuse
current-opline semantics.

The function should:

- fast-path unrestricted classes;
- normalize or compare canonical namespace strings;
- implement exact private and descendant protected checks;
- build diagnostics without absolute file paths;
- receive enough context to distinguish `new`, `extends`, `implements`,
  `trait use`, reflection construction, callable resolution, and type
  resolution.

Preferred fast path:

```c
if (!(ce->ce_flags2 & ZEND_ACC2_NAMESPACE_RESTRICTED)) {
    return SUCCESS;
}
```

## Caller Namespace

The caller namespace should come from lexical compile context:

- main op array namespace for top-level code;
- function/closure op array namespace for functions and closures;
- effective class method namespace for methods;
- using class namespace for composed trait methods;
- original user call site namespace for internal functions resolving class
  names or callables.

Gate 3 stores lexical namespace source spelling on user op arrays and a
lightweight namespace range table for top-level/eval op arrays that contain
multiple namespace blocks. The checker normalizes caller namespace internally
for comparisons and uses the stored spelling for diagnostics. `Closure::bind()`
and `Closure::call()` do not change the lexical namespace of the executed
closure body.

## Enforcement Points

The central check must be invoked from or before:

- `zend_fetch_class_by_name()`;
- `zend_lookup_class_ex()` callers that represent actual use, not mere
  existence probes;
- `ZEND_NEW`;
- `ZEND_INIT_STATIC_METHOD_CALL`;
- `ZEND_FETCH_STATIC_PROP_*`;
- `ZEND_FETCH_CLASS_CONSTANT`;
- inheritance linking in `zend_do_link_class()`;
- trait/interface/parent resolution;
- type resolution for properties, parameters, returns, class constants, union,
  intersection, and DNF types;
- callable resolution and invocation;
- reflection construction paths if enforcement is chosen;
- unserialize and other engine-created object construction paths.

Gate 3 wires the VM/runtime, linking, type, callable, selected Reflection
allocation, alias-use, and unserialize paths listed in
[05-operation-coverage.md](../07-risk-closure/05-operation-coverage.md).
OPcache/preload/JIT validation and performance evidence remain later gates.

## Runtime Cache Hazards

If an opcode cache slot stores a resolved CE, the next execution from a
different namespace must not skip access checking. Options:

- do the visibility check on every use after retrieving cached CE;
- include caller namespace in cache key where feasible;
- cache only unrestricted classes in fast paths;
- store a "checked for this caller" marker only if keyed by caller identity.

The simplest correct prototype is to always check restricted CEs after cache
lookup. The fast path makes unrestricted classes cheap.

Gate 3 checks restricted CEs after representative semantic cache lookups,
including `ZEND_NEW`, `ZEND_FETCH_CLASS`, static access, class constants,
`instanceof`, and callable class-string resolution. OPcache/preload/JIT cache
behavior remains Gate 4+.

## Error Messages

Preferred runtime shape:

```text
Error: Cannot access private(namespace) class Acme\Billing\Internal\Service from namespace App\Controller
```

Diagnostics should include:

- target class-like kind and full name;
- visibility kind;
- caller namespace, with a stable spelling for global namespace;
- allowed namespace or root.

Diagnostics should not include absolute file paths unless an existing
compile/link error path requires them.

## Name Model vs Runtime Membrane

The enforcement function checks class-like name use. It should not be called on
every instance method call merely because an object's actual class is
restricted. Existing member visibility remains separate.

Allowed:

```php
$service = PublicFactory::create();
$service->execute();
```

Denied:

```php
new Acme\Billing\ServiceImpl();
Acme\Billing\ServiceImpl::class; // unresolved policy
```
