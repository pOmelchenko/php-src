# Other Languages

Access date: 2026-06-21. This page compares official or primary language
sources only.

## D

- Source: D language specification, Visibility Attributes,
  <https://dlang.org/spec/attribute.html>
- Source status: official language specification
- Last known generated date observed: 2026-06-21

Model:

- Isolation unit: module/package.
- It is a real module system, not only a namespace string.
- `package` exposes within the current package.
- `package(root)` exposes within the package tree rooted at `root`.
- `root` is expected to be a package ancestor or otherwise related package
  root, not an arbitrary unrelated name.
- Child packages can inherit package-root visibility.
- Friend classes are not the primary mechanism.
- Restrictions apply to declarations/names.

Applicable to PHP:

- D is strong prior art for `protected(namespace: Root)`-style explicit root.
- The "root must be an ancestor" rule maps well to full namespace segments.

Not directly applicable:

- D packages are real modules. PHP namespaces are not.
- PHP cannot assume file layout, package ownership, or build-system module
  boundaries from namespace text alone.

## Rust

- Source: Rust Reference, Visibility and Privacy,
  <https://doc.rust-lang.org/reference/visibility-and-privacy.html>
- Source status: official language reference

Model:

- Isolation unit: module tree.
- It is a real module system.
- Items are private to the current module by default.
- Private items are visible to the current module and its descendants.
- `pub(self)`, `pub(super)`, `pub(crate)`, and `pub(in path)` express qualified
  visibility.
- `pub(in path)` must name an ancestor module of the item.
- Re-exports can deliberately expose public items through another path.
- The restriction applies to item names, not to object identity after a value is
  obtained.

Applicable to PHP:

- Rust is strong prior art for explicit ancestor-scoped visibility.
- `pub(in ancestor)` supports the future root idea.
- The distinction between name visibility and object use supports the name
  model.

Not directly applicable:

- Rust modules are lexical and file/module-system backed.
- Rust has a strong compile-time type system and whole-crate compilation.
- PHP autoloading and dynamic names make many checks runtime concerns.

## Scala

- Source: Scala 2.13 Language Specification,
  <https://www.scala-lang.org/files/archive/spec/2.13/>
- Source status: official language specification archive

Model:

- Isolation unit: package, class, object, and path-qualified scopes.
- Scala packages are language namespaces with compiler-enforced access.
- Qualified visibility such as `private[p]` restricts access to code inside
  package or enclosing entity `p`.
- `private[this]` and related forms can restrict access more narrowly.
- Child package behavior depends on qualified scope and Scala package nesting.
- The restriction applies to members and declarations as names.

Applicable to PHP:

- Qualified visibility syntax is prior art for naming a visibility root.
- It shows that "private" can be parameterized without replacing normal
  `private` meaning.

Not directly applicable:

- Scala has a stronger static compiler model than PHP.
- Scala package nesting and access rules do not map one-to-one to PHP namespace
  strings.

## Java

- Source: Java Language Specification, Java SE 24,
  <https://docs.oracle.com/javase/specs/jls/se24/html/>
- Source status: official specification
- Last known page metadata observed: 2026-03-16

Model:

- Isolation unit: package, with optional module export boundaries.
- Top-level classes/interfaces are accessible outside their package only if
  declared `public` and, for named modules, if the package is exported.
- Package names may be hierarchical, but subpackages do not inherit package
  access. `oliver` and `oliver.twist` are distinct packages.
- Java modules can export or open packages to selected other modules.
- Friend-like access exists at the module export/open level, not as class
  friendship.

Applicable to PHP:

- Java supports package-private top-level declarations as mainstream prior art.
- Java warns against assuming dotted package hierarchy means access hierarchy.

Not directly applicable:

- PHP's working `protected(namespace)` explicitly wants descendant namespaces.
- PHP namespaces are not Java packages or modules.

## C#

- Source: C# `internal` reference,
  <https://learn.microsoft.com/en-us/dotnet/csharp/language-reference/keywords/internal>
- Source: `InternalsVisibleToAttribute`,
  <https://learn.microsoft.com/en-us/dotnet/api/system.runtime.compilerservices.internalsvisibletoattribute>
- Source status: official Microsoft documentation
- Last known update observed: 2026-01-26 for `internal`, 2026-06-12 for
  `InternalsVisibleToAttribute`

Model:

- Isolation unit: assembly.
- `internal` exposes within the same assembly.
- `InternalsVisibleToAttribute` grants friend assembly access.
- There is no descendant namespace inheritance for `internal`.
- Restrictions apply to type/member names based on assembly boundary.

Applicable to PHP:

- C# is useful prior art for why `internal` needs a real assembly/module
  boundary.
- Friend assemblies show a separate friend mechanism.

Not directly applicable:

- Composer package, PHP namespace, and deployable PHP code are not equivalent
  to a .NET assembly.
- Adding `internal` without a PHP module/package boundary would be misleading.

## Kotlin

- Source: Kotlin visibility modifiers,
  <https://kotlinlang.org/docs/visibility-modifiers.html>
- Source status: official Kotlin documentation
- Last known update observed: 2025-11-13; page build observed 2026-06-18

Model:

- Isolation unit: file, class, and module.
- Top-level `private` is file-private.
- Top-level `internal` is visible in the same module.
- Top-level `protected` is not available.
- A module is a set of Kotlin files compiled together.

Applicable to PHP:

- Kotlin supports the idea that top-level `private` may mean file-private, which
  is a conflict to avoid if PHP uses plain `private class`.
- Kotlin reinforces that `internal` needs a module definition.

Not directly applicable:

- PHP lacks a compile-together module unit.
- PHP files can be loaded independently and dynamically.

## Swift

- Source: The Swift Programming Language, Access Control,
  <https://docs.swift.org/swift-book/documentation/the-swift-programming-language/accesscontrol/>
- Source status: official Swift documentation
- Version observed: Swift 6.3 documentation data

Model:

- Isolation unit: declaration, source file, module, and package.
- `private` restricts to enclosing declaration and same-file extensions.
- `fileprivate` restricts to the current source file.
- `internal` restricts to the current module.
- `package` restricts to the current package.
- `public` and `open` cross module boundaries.
- A package is configured by the build system, not only by source namespace.

Applicable to PHP:

- Swift shows a layered access model: file, module, package, public.
- It supports reserving `internal` for a future module/package system rather
  than overloading namespaces.

Not directly applicable:

- Swift packages/modules are build-system concepts.
- PHP currently has no equivalent engine-level package identity.

## Go

- Source: Go language specification, <https://go.dev/ref/spec>
- Source status: official language specification
- Language version observed: go1.26, 2026-01-12

Model:

- Isolation unit: package.
- An identifier is exported if it begins with an uppercase Unicode letter and is
  declared in a package block or is a field/method name.
- Non-exported identifiers are package-private.
- Child directories/packages do not inherit access.
- There is no friend mechanism.
- Reflection can observe values but cannot make unexported identifiers ordinary
  exported API.

Applicable to PHP:

- Go is strong prior art for simple package-private naming rules.

Not directly applicable:

- Go packages are compilation units.
- PHP namespaces do not imply package identity or file layout.

