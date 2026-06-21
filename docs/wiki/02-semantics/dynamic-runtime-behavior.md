# Dynamic Runtime Behavior

The check must use lexical caller namespace. It must not depend on
`debug_backtrace()`, current object, current class table state, or autoload
order.

## Call Contexts

| Context | Caller namespace source | Notes |
| --- | --- | --- |
| Top-level code | Namespace active for the compiled main op array | Files without namespace use global namespace. |
| Named function | Namespace in which the function was declared | Function may be called from anywhere; caller for internal class-name use inside function is the function's lexical namespace. |
| Instance method | Namespace of declaring/effective class method op array | Do not use runtime object namespace except for trait model decisions. |
| Static method | Namespace of method op array | Same as instance method. |
| Closure | Namespace captured on the closure op array at compile time | Matches prior art from PR #20421. |
| Arrow function | Same as closure | Must not inherit caller namespace at invocation. |
| `Closure::bind()` | Binding object/scope does not change lexical namespace | Otherwise visibility could be bypassed by rebinding. |
| `eval()` | Namespace in evaluated code, or compiler's current eval namespace behavior | Needs PHPTs for explicit namespace, inherited namespace, and global eval. |
| Internal functions | Use lexical namespace of the user call site when resolving user-supplied class names or callables | Examples: `call_user_func`, `is_callable`, `class_exists`. |
| Reflection | Either use call-site namespace for privileged operations or document a privileged bypass | Current recommendation: read metadata, enforce construction. |
| Engine-generated operations | Must carry enough context or be explicitly classified as privileged | Examples: unserialize, preload, attributes, type checks. |

## Trait Semantics

Two models are possible for operations written inside a trait method.

### Model 1: Trait Declaration Namespace

The lexical namespace of the trait file controls access for operations written
inside the trait.

Pros:

- Directly follows source location.
- A trait cannot gain extra access by being used in an allowed namespace.

Cons:

- PHP trait methods are composed into using classes.
- It differs from PR #20421's member-visibility direction, where trait methods
  are treated under the receiver class namespace after composition.
- A trait intended as internal implementation glue becomes harder to reuse
  within an allowed namespace if declared elsewhere.

### Model 2: Using Class Namespace

After trait composition, operations written in trait methods use the namespace
of the using class/effective method scope.

Pros:

- Aligns with current trait composition intuition.
- Aligns with PR #20421's stated trait behavior for namespace-private members.
- Makes the using class responsible for access.

Cons:

- A trait can gain access depending on where it is used.
- Static analyzers must account for each use site.

Prototype decision:

- For checking `use RestrictedTrait;`, use the namespace of the using class
  declaration.
- For operations inside composed trait methods, prefer the using class namespace
  for the first prototype, matching PR #20421's trait direction.
- Keep this as `accepted-for-prototype`, not final RFC text, until tests cover
  trait declaration namespace, using class namespace, aliases, and adaptations.

## Runtime Cache Requirements

Runtime caches may cache resolved class entries, method handlers, properties,
or constants. A cache hit must not skip the namespace access check unless the
cache key includes the caller namespace or the cached path is known to be
unrestricted.

Required order tests:

- allowed use loads and caches the class, then denied use must still fail;
- denied use occurs first, then allowed use must still succeed;
- alias created after allowed load must not widen access.

