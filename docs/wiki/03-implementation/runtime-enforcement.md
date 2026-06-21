# Runtime Enforcement

## Central Check

The implementation should avoid scattering incompatible checks across each
opcode. A central function should own the rule:

```c
zend_result zend_check_class_namespace_visibility(
    const zend_class_entry *target_ce,
    const zend_string *caller_namespace,
    zend_class_visibility_context context
);
```

The exact API is a planning placeholder, not a proposed public Zend API.

Current Phase C prototype API:

```c
bool zend_check_class_namespace_visibility(const zend_class_entry *ce);
```

The prototype API derives caller namespace internally from the currently
executing named user function or method. This is intentionally incomplete and
should not be treated as the final API shape.

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

Current php-src does not store namespace directly on `zend_op_array`; PR #20421
adds such a field. Class-like visibility may need the same field or a more
targeted caller-context mechanism.

Current Phase C limitation:

- named function namespace is derived from `op_array.function_name`;
- method namespace is derived from `op_array.scope->name`;
- global/top-level code currently resolves to global namespace;
- closures and arrow functions without class scope currently do not carry their
  lexical namespace;
- `Closure::bind()` and eval need a stronger design before this can be called
  complete.

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

Current Phase C wired points:

- `ZEND_NEW`;
- `ZEND_FETCH_CLASS`.

This covers static and dynamic `new` in the focused tests. It does not yet
cover the full operation matrix.

## Runtime Cache Hazards

If an opcode cache slot stores a resolved CE, the next execution from a
different namespace must not skip access checking. Options:

- do the visibility check on every use after retrieving cached CE;
- include caller namespace in cache key where feasible;
- cache only unrestricted classes in fast paths;
- store a "checked for this caller" marker only if keyed by caller identity.

The simplest correct prototype is to always check restricted CEs after cache
lookup. The fast path makes unrestricted classes cheap.

The current Phase C spike checks restricted CEs after `ZEND_NEW` and
`ZEND_FETCH_CLASS` cache lookup, which is why the first cache-order PHPTs cover
both allowed-then-denied and denied-then-allowed flows.

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
