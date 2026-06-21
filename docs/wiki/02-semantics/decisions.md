# Decision Log

Decisions marked `accepted-for-prototype` are implementation-direction choices
for a possible local spike, not accepted PHP language decisions.

## DEC-001: Plain `private`/`protected` or Qualified Modifiers

Status: accepted-for-prototype

Context:
Top-level class-like visibility needs syntax that does not confuse member
visibility, inheritance-based `protected`, or future file/module visibility.

Options:

- plain `private class` / `protected class`;
- qualified `private(namespace)` / `protected(namespace)`;
- `internal`;
- built-in attribute.

Decision:
Use `private(namespace)` and `protected(namespace)` for the first prototype.

Consequences:
The syntax is explicit, keeps `private(file)` available, and avoids adding
`internal` without a module boundary.

Evidence:
Current PHP has `private(set)` and `protected(set)` token precedents. PR #20421
uses `private(namespace)` for members/properties.

## DEC-002: Exact Private Semantics

Status: accepted-for-prototype

Context:
`private(namespace)` must define whether child namespaces have access.

Options:

- exact namespace only;
- namespace plus descendants;
- file-only.

Decision:
Use exact namespace equality.

Consequences:
`Acme\Billing\Application` cannot access a `private(namespace)` declaration in
`Acme\Billing`.

Evidence:
Matches PR #20421's exact lexical namespace direction for
`private(namespace)`.

## DEC-003: Descendant Protected Semantics

Status: accepted-for-prototype

Context:
`protected(namespace)` must define a namespace-tree rule.

Options:

- exact namespace only;
- declaration namespace plus descendants;
- common-prefix package model;
- inheritance/subclass model.

Decision:
Use declaration namespace plus descendant namespaces by complete segments.

Consequences:
`Acme\Billing\Application` is allowed; `Acme\BillingExtra` is denied.

Evidence:
This matches the motivating requirement and aligns with D/Rust ancestor-root
prior art when future explicit roots are considered.

## DEC-004: Lexical Caller

Status: accepted-for-prototype

Context:
The caller identity could come from source lexical namespace, runtime stack, or
current object.

Options:

- lexical namespace of the operation;
- `debug_backtrace()`/runtime caller;
- current object or current class;
- autoloader namespace.

Decision:
Use lexical namespace of the operation.

Consequences:
Closures, eval, traits, reflection, and internal callbacks need explicit
metadata/context. Autoload order cannot change access.

Evidence:
PR #20421 uses lexical namespace for member namespace visibility.

## DEC-005: Trait Semantics

Status: accepted-for-prototype

Context:
Operations written inside trait methods can be attributed to the trait
declaration namespace or the using class namespace.

Options:

- trait declaration namespace;
- using class namespace after composition.

Decision:
For the first prototype, use the using class namespace after composition for
operations inside trait methods. For `use RestrictedTrait`, check the using
class declaration namespace.

Consequences:
Trait access may vary by use site. Static analysis and tests must cover this.

Evidence:
This aligns with PR #20421's treatment of trait methods for namespace-private
members.

## DEC-006: Object Escape

Status: accepted-for-prototype

Context:
A restricted concrete class may implement a public interface and be returned by
a public factory.

Options:

- restrict name use only;
- check every method/property operation on the runtime object's actual class.

Decision:
Use the name model.

Consequences:
External code may use an obtained object through public API without naming the
restricted concrete class.

Evidence:
The old class-like namespace visibility RFC also allowed operations on already
obtained objects. The motivating factory/interface pattern requires this.

## DEC-007: `::class`

Status: unresolved

Context:
`SomeClass::class` normally produces a string without autoload.

Options:

- always allow string production and enforce later;
- check only if target is already loaded;
- force autoload/check.

Decision:
Unresolved. The least disruptive option is to allow string production and
enforce when the string is used as a class name.

Consequences:
Class strings for restricted classes may be visible.

Evidence:
Changing `::class` into an autoloading operation would be a significant PHP
behavior change.

## DEC-008: Aliases

Status: accepted-for-prototype

Context:
`class_alias()` can introduce another name for the same class entry.

Options:

- alias widens visibility;
- alias preserves class-entry visibility.

Decision:
Alias preserves visibility because metadata belongs to `zend_class_entry`.

Consequences:
A public-looking alias cannot bypass restrictions.

Evidence:
OPcache persistence code already comments that the same `zend_class_entry` may
be reused by `class_alias()`.

## DEC-009: Reflection

Status: proposed

Context:
Reflection already has privileged behavior for some member operations.

Options:

- reflection fully bypasses class-like visibility;
- reflection can read metadata but construction enforces visibility;
- reflection always enforces every operation.

Decision:
Propose metadata-readable, construction-checked reflection.

Consequences:
`new ReflectionClass()` may inspect a restricted class, but
`newInstance()` should not be a construction bypass unless RFC voters choose
that explicitly.

Evidence:
Reflection member bypass is prior art but does not automatically settle
class-like name access.

## DEC-010: Public API Exposure

Status: unresolved

Context:
Public signatures can mention restricted types.

Options:

- fully forbid inconsistent accessibility;
- allow and check use;
- defer to a later RFC.

Decision:
Defer for first prototype while documenting risks.

Consequences:
The first implementation should not claim full API consistency.

Evidence:
PHP autoloading and lazy type resolution make global compile-time enforcement
difficult.

## DEC-011: Global Namespace

Status: unresolved

Context:
An empty namespace breaks the naive descendant-prefix rule.

Options:

- reject global `protected(namespace)`;
- treat it as exact global;
- allow it to mean public.

Decision:
Reject or exact-global are viable; public-by-accident is rejected.

Consequences:
The RFC must explicitly define global behavior.

Evidence:
The requirement says global root must not accidentally turn restricted class
into public.

## DEC-012: Explicit Root

Status: proposed

Context:
Future syntax may name an ancestor root.

Options:

- include in first prototype;
- document as Future Scope;
- support arbitrary roots or friends.

Decision:
Document as Future Scope only. Root must be declaration namespace or ancestor.

Consequences:
Base implementation stays smaller.

Evidence:
D `package(root)` and Rust `pub(in ancestor)` support the ancestor-root idea,
but PHP needs base semantics first.

## DEC-013: Error Type

Status: proposed

Context:
Violations may happen at compile, link, autoload, or runtime.

Options:

- always `Error`;
- compile/link fatal when operation is declaration linkage, `Error` for runtime;
- custom exception.

Decision:
Prefer `Error` for runtime access violations; use existing compile/link fatal
paths for declaration-time failures.

Consequences:
Messages should include target class, caller namespace, and allowed scope, but
not absolute file paths.

Evidence:
PHP already uses fatal compile/link errors and runtime `Error` for many engine
violations.

## DEC-014: Class-like Coverage

Status: accepted-for-prototype

Context:
The modifier must define applicable declarations.

Options:

- only classes;
- classes/interfaces/traits/enums;
- include anonymous classes.

Decision:
Support named class, interface, trait, and enum declarations. Exclude anonymous
classes.

Consequences:
Parser and metadata need to cover all `ZEND_AST_CLASS` class-kind flags except
anonymous classes.

Evidence:
The user-level goal requires class-like declarations; anonymous classes do not
have stable top-level names.

## DEC-015: `internal`

Status: accepted-for-prototype

Context:
Several languages have `internal`, but they also have modules/assemblies.

Options:

- add `internal` as namespace visibility;
- reserve `internal` for future modules/packages;
- never use `internal`.

Decision:
Do not add `internal` in the first prototype; reserve it for a real module or
package boundary discussion.

Consequences:
The prototype does not conflate namespace and Composer package.

Evidence:
C#, Kotlin, and Swift all tie `internal`-style visibility to a real compilation
or package unit.

