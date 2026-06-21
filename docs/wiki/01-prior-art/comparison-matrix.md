# Comparison Matrix

This matrix compares prior art against the PHP working hypothesis. "Name" means
the primary restriction is on using the declaration name, not on every operation
performed on a value after it has escaped.

| Language | Isolation unit | Real module/package? | Descendants inherit? | Explicit root? | Root must be ancestor? | Friend mechanism? | Restricts | Alias/re-export/reflection notes | PHP applicability |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| PHP current | Namespace string | No | No engine restriction | No | N/A | No general friend | Mostly members | `class_alias()` aliases class entries; reflection sees loaded symbols | Baseline gap |
| PHP RFC namespace_visibility | Lexical namespace for members | No | No; exact only | No | N/A | No | Method/property access | Reflection member behavior is privileged; OPcache stores op_array namespace | Strong prior art for lexical caller |
| Old PHP namespace-visibility RFC | Namespace string | No | Proposed model differs | No | N/A | No | Class/interface/trait names | Reflection considered; old runtime model incomplete | Prior art only |
| D | Package/module | Yes | Yes for package root | Yes, `package(root)` | Yes/related root | Not primary | Name/declaration | Module system enforces access | Best prior art for explicit root |
| Rust | Module tree | Yes | Private visible to descendants; `pub(in)` uses ancestor | Yes | Yes | No class friends | Item name | Re-export is explicit language feature | Best prior art for ancestor-qualified scope |
| Scala | Package/class/object scopes | Yes | Qualified scope dependent | Yes, `private[p]` | Scope must be meaningful enclosing/qualified path | Not primary | Declaration/member names | Compiler controls access | Useful syntax prior art |
| Java | Package/module | Yes | No subpackage inheritance | Module exports can be qualified | N/A for package-private | Qualified module exports/opens | Top-level type name | Reflection/module access has separate rules | Shows package-private and warns about hierarchy |
| C# | Assembly | Yes | No namespace inheritance | No namespace root | N/A | Friend assemblies | Type/member names | Attribute grants assembly friendship | `internal` needs real boundary |
| Kotlin | File/module/class | Yes | No namespace-tree rule | No namespace root | N/A | Friend paths in compiler tooling, not source-level class friends | Declarations/members | Reflection not a module boundary | `private class` conflicts with file-private expectations |
| Swift | Declaration/file/module/package | Yes | No namespace-tree rule | No namespace root | N/A | No source friend classes | Declarations/members | Access tied to module/package build identity | `internal`/`package` need build identity |
| Go | Package | Yes | No child inheritance | No | N/A | No | Identifier name | Export is naming convention; reflection separate | Simple package-private precedent |

## Implications

- The closest analogs for `protected(namespace: Root)` are D `package(root)` and
  Rust `pub(in ancestor)`.
- The closest analog for current PHP without modules is the old PHP
  namespace-visibility draft, but its syntax and semantics should not be reused
  uncritically.
- `internal` should not be in the first prototype because every mature
  `internal`-style feature above relies on a real module, assembly, package, or
  compile unit.
- Plain `private class` is risky because Kotlin and current PHP file-private
  discussions make top-level `private` naturally read as file-private.
- Java and Go caution that hierarchical textual package names do not always
  imply visibility inheritance. PHP must explicitly justify descendant
  namespace access if it chooses it.

