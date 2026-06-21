# php-src Map

Repository snapshot:

- Commit: `0fff3ccce2f5f9e0695502a509fa8e8edf8f77d4`
- Branch: `packages`
- PHP version: `8.6.0-dev`
- Access date: 2026-06-21

This map is based on local source inspection using `rg` and targeted reads. It
does not claim that implementation is complete.

## Lexer and Parser Grammar

Files:

- `Zend/zend_language_scanner.l`
- `Zend/zend_language_parser.y`
- `Zend/zend_ast.h`
- `Zend/zend_ast.c`

Observed tokens and grammar:

- `T_PRIVATE` is returned in `zend_language_scanner.l` around line 1778.
- `T_PROTECTED` is returned around line 1782.
- `T_PROTECTED_SET` and `T_PRIVATE_SET` exist around lines 1790 and 1794.
- Parser token declarations for `T_PRIVATE`, `T_PROTECTED`,
  `T_PRIVATE_SET`, `T_PROTECTED_SET`, `T_READONLY`, `T_CLASS`, `T_TRAIT`,
  `T_INTERFACE`, and `T_ENUM` are around `zend_language_parser.y` lines
  154-169.
- `class_declaration_statement` is around lines 603-609.
- `class_modifiers` and `class_modifier` are around lines 612-633.
- Current `class_modifier` covers `abstract`, `final`, and `readonly`.
- Trait, interface, and enum declarations are separate grammar alternatives
  around lines 637-651 and currently do not take `class_modifiers`.
- Anonymous class modifiers are handled around lines 618-627 and 1229-1232.

AST:

- `ZEND_AST_CLASS` is declared in `Zend/zend_ast.h` around line 45.
- Class-like declarations use `zend_ast_create_decl(ZEND_AST_CLASS, flags, ...)`
  in parser actions.
- `ZEND_AST_CLASS_NAME` and `ZEND_AST_CLASS_CONST` cover class-name and
  class-constant AST nodes.

Implementation implications:

- Base syntax can follow the scanner-token style used by `private(set)` and
  `protected(set)`, but class-like namespace visibility needs new parser flags.
- Supporting interfaces, traits, and enums requires extending more than the
  `class_modifiers T_CLASS` production.
- Anonymous classes should reject namespace visibility modifiers even if other
  class modifiers remain allowed.

## Compiler Context

Files:

- `Zend/zend_compile.h`
- `Zend/zend_compile.c`

Important structures and fields:

- `zend_file_context` is declared in `Zend/zend_compile.h` around line 110 and
  contains `zend_string *current_namespace`.
- `zend_op_array` is declared around line 532. In this commit it has
  `function_name`, `scope`, `prototype`, `attributes`, `run_time_cache`,
  `doc_comment`, `cache_size`, `opcodes`, `filename`, and line fields, but no
  `namespace_name` field.
- `ZEND_COMPILE_DELAYED_BINDING` is defined around line 1285.

Compiler functions and paths:

- `zend_resolve_non_class_name()` around `zend_compile.c` line 1091.
- `zend_resolve_class_name()` around line 1161.
- `zend_resolve_class_name_ast()` around line 1224.
- `zend_compile_class_ref()` around line 2855.
- `zend_compile_static_call()` around line 5586.
- `zend_compile_new()` around line 5664.
- `zend_compile_typename()` and `zend_compile_typename_ex()` declarations around
  lines 7535-7537; definition around line 7766.
- `zend_compile_class_const_decl()` around line 9295.
- `zend_compile_class_decl()` definition around line 9533.
- Namespace declaration handling is around lines 9876 and 10048-10085.
- `T_NS_C` / `__NAMESPACE__` handling reads `FC(current_namespace)` around line
  10214.
- `zend_compile_instanceof()` around line 11064.
- `zend_compile_class_const()` around line 11360.

Implementation implications:

- Declaration namespace is available during compile through
  `CG(file_context).current_namespace`.
- For runtime operations in functions/closures, current php-src does not
  persist lexical namespace on `zend_op_array`; PR #20421 adds such a field.
- Type positions and class declarations are compiled through different paths
  and cannot be covered only by `new` handling.

## Class Entry

File:

- `Zend/zend.h`

Observed structure:

- `struct _zend_class_entry` starts around line 151.
- Key fields include `type`, `name`, parent union, `refcount`, `ce_flags`,
  `ce_flags2`, function/property/constant tables, mutable data,
  `inheritance_cache`, interface and trait metadata, attributes, enum metadata,
  and `doc_comment`.
- There is no dedicated namespace-visibility metadata field in this commit.

Existing class flags:

- `ZEND_ACC_PUBLIC`, `ZEND_ACC_PROTECTED`, `ZEND_ACC_PRIVATE` are in
  `Zend/zend_compile.h` around lines 219-221.
- `ZEND_ACC_INTERFACE`, `ZEND_ACC_TRAIT`, `ZEND_ACC_ANON_CLASS`, and
  `ZEND_ACC_ENUM` are around lines 281-284.
- `ZEND_ACC_LINKED` is around line 287.
- `ZEND_ACC_READONLY_CLASS` is around line 311.
- `ZEND_ACC_PPP_MASK` and `ZEND_ACC_PPP_SET_MASK` are around lines 421-422.

Implementation implications:

- Class-like namespace visibility likely needs one or more `ce_flags` bits plus
  one or two `zend_string *` fields for declaration namespace/effective root.
- Adding fields to `zend_class_entry` is ABI-impacting.
- A fast path should use a single flag check before touching namespace strings.

## Class Fetching and Autoload

Files:

- `Zend/zend_execute_API.c`
- `Zend/zend_execute.h`
- `ext/spl/php_spl.c`

Functions:

- `zend_lookup_class_ex()` at `zend_execute_API.c` line 1179.
- `zend_lookup_class()` at line 1296.
- `zend_fetch_class_by_name()` at line 1793.
- `zend_autoload` function pointer at line 53.
- `zend_perform_class_autoload()` is called by `spl_autoload_call()` in
  `ext/spl/php_spl.c` around line 388.
- `spl_find_ce_by_name()` uses `zend_lookup_class()` around
  `ext/spl/php_spl.c` line 51.

Implementation implications:

- Metadata is known only after a CE is found or autoload completes.
- The original lexical caller namespace must be passed through class fetch
  paths. The autoloader's own namespace must not become the caller.
- Existence probes that use `zend_lookup_class()` need a policy decision:
  reveal existence or enforce access.

## VM Opcodes and Handlers

Files:

- `Zend/zend_vm_opcodes.h`
- `Zend/zend_vm_def.h`

Important opcodes:

- `ZEND_NEW` opcode 68.
- `ZEND_FETCH_CONSTANT` opcode 99.
- `ZEND_CATCH` opcode 107.
- `ZEND_FETCH_CLASS` opcode 109.
- `ZEND_INIT_STATIC_METHOD_CALL` opcode 113.
- `ZEND_INSTANCEOF` opcode 138.
- `ZEND_DECLARE_CLASS` opcode 144.
- `ZEND_DECLARE_CLASS_DELAYED` opcode 145.
- `ZEND_DECLARE_ANON_CLASS` opcode 146.
- `ZEND_FETCH_CLASS_NAME` opcode 157.
- `ZEND_FETCH_STATIC_PROP_R/RW/W/IS/UNSET/FUNC_ARG` opcodes 173-178.
- `ZEND_FETCH_CLASS_CONSTANT` opcode 181.

Observed handlers:

- `ZEND_INIT_STATIC_METHOD_CALL` handler around `zend_vm_def.h` line 3761;
  class lookup around line 3776.
- `ZEND_CATCH` handler around line 4851; fetches catch class with
  `ZEND_FETCH_CLASS_NO_AUTOLOAD | ZEND_FETCH_CLASS_SILENT` around line 4864.
- `ZEND_NEW` handler around line 5978; fetches class around line 5990.
- `ZEND_FETCH_CLASS_CONSTANT` handler around line 6128; class fetch around
  line 6149.
- `ZEND_DECLARE_CLASS` handler around line 8004.
- `ZEND_DECLARE_CLASS_DELAYED` handler around line 8013.
- `ZEND_DECLARE_ANON_CLASS` handler around line 8033.
- `ZEND_INSTANCEOF` handler around line 8086; `zend_lookup_class_ex()` with
  `NO_AUTOLOAD` around line 8102.
- `ZEND_FETCH_CLASS_NAME` handler around line 8972.

Implementation implications:

- Enforcement cannot live only in one opcode handler.
- A central check should be called from class fetch/link/type/reflection paths.
- `CATCH`, `INSTANCEOF`, and `FETCH_CLASS_NAME` have special no-autoload or
  string behavior and need dedicated policy decisions.

## Inheritance, Interfaces, and Traits

File:

- `Zend/zend_inheritance.c`

Functions:

- `zend_do_implement_interface()` around line 2204.
- `zend_do_traits_method_binding()` around line 2682.
- `zend_do_traits_constant_binding()` around line 2818.
- `zend_do_traits_property_binding()` around line 2875.
- `zend_do_link_class()` around line 3485.

Observed link path:

- Parent lookup/fetch around line 3503.
- Trait resolution around line 3517.
- Interface resolution around line 3551.
- Inheritance cache use around lines 3575-3783.
- Interface implementation around line 3691.
- Method override checks around line 3749.

Implementation implications:

- Extends, implements, interface extends, and trait use must be checked here or
  before this path finalizes links.
- Inheritance cache must not reuse a linked CE in a way that skips access checks
  for a different caller namespace.

## Callables and Member Access

Files:

- `Zend/zend_API.c`
- `Zend/zend_object_handlers.c`
- `Zend/zend_execute.c`
- `Zend/zend_vm_def.h`

Functions and checks:

- `_call_user_function_impl()` is declared in `Zend/zend_API.h` around line 691.
- Callable validation paths in `Zend/zend_API.c` include
  `zend_check_method_accessible()` use around lines 3953 and 4016.
- `zend_std_get_static_method()` is in `Zend/zend_object_handlers.c` around
  line 2030.
- `zend_check_protected()` is around line 1740.
- `zend_check_property_access()` is around line 540.
- `zend_get_property_info()` is around line 475.

Implementation implications:

- Existing member visibility checks are not enough; class-like visibility must
  be checked before or during target CE resolution for class-string callables.
- Internal functions resolving user callables need the user's lexical namespace.

## Reflection

Files:

- `ext/reflection/php_reflection.c`
- `ext/reflection/php_reflection.stub.php`
- generated arginfo under `ext/reflection`

Relevant methods and lines:

- `ReflectionClass::__construct()` around line 4117.
- `ReflectionClass::newInstance()` around line 5023.
- `ReflectionClass::newInstanceWithoutConstructor()` around line 5070.
- `ReflectionClass::newInstanceArgs()` around line 5090.
- `ReflectionClass::isSubclassOf()` around line 5500.
- `ReflectionClass::implementsInterface()` around line 5533.
- `ReflectionAttribute::newInstance()` around line 7538.
- `ReflectionMethod::invoke()` path around lines 3409-3517.
- `ReflectionMethod::setAccessible()` around line 3804.
- `ReflectionProperty::setAccessible()` around line 6401.
- Stub methods `ReflectionClass::isInternal()` and `isUserDefined()` already
  use the term "internal" for engine/user-defined distinction.

Implementation implications:

- New reflection metadata should avoid overloading "internal".
- Reflection construction and invocation need explicit policy.
- Stub and arginfo regeneration is required if new methods are added.

## OPcache, Preload, and JIT

Files:

- `ext/opcache/zend_persist.c`
- `ext/opcache/zend_persist_calc.c`
- `ext/opcache/ZendAccelerator.c`
- `ext/opcache/jit/zend_jit.c`
- `ext/opcache/jit/zend_jit_helpers.c`

Persistence:

- `zend_persist_op_array_ex()` around `zend_persist.c` line 390.
- `zend_persist_class_entry()` around line 916.
- `zend_persist_class_entry_calc()` around `zend_persist_calc.c` line 450.
- Class-entry persistence comments note class entries may be reused by
  `class_alias()` around `zend_persist.c` line 922 and
  `zend_persist_calc.c` line 455.

Inheritance cache and preload:

- OPcache inheritance cache hooks in `ZendAccelerator.c` around lines 2289,
  2334, 2367, and hook setup around 3467-3470.
- Preload dependency resolution around lines 3845-3874.
- Preload link path calls `zend_do_link_class()` around line 4098.
- Trait method preload fix paths around lines 4340-4391.

JIT:

- `zend_get_known_class()` in `ext/opcache/jit/zend_jit.c` around line 567.
- Trait lookup in JIT analysis around line 737.
- JIT class helper `zend_jit_find_class_helper()` in
  `ext/opcache/jit/zend_jit_helpers.c` around line 188.

Implementation implications:

- Any new CE/op_array strings must be counted and persisted.
- JIT helpers and known-class assumptions must not bypass runtime access checks.
- Preload must store metadata and preserve behavior after request startup.

## Test Directories

Relevant existing directories:

- `Zend/tests/access_modifiers`
- `Zend/tests/traits`
- `Zend/tests/type_declarations`
- `Zend/tests/attributes`
- `Zend/tests/enum`
- `Zend/tests/closures`
- `ext/reflection/tests`
- `ext/opcache/tests`
- `ext/spl/tests`
- `ext/tokenizer/tests`

PR #20421 adds many tests under `Zend/tests/access_modifiers` with
`private_namespace_*` names. Those are useful patterns but target members and
properties, not class-like declarations.

## PR #20421 Delta

Reusable:

- scanner/parser pattern for `private(namespace)`;
- `op_array->namespace_name` idea for lexical caller namespace;
- tests for closures, callables, eval, traits, inheritance, reflection, and
  OPcache persistence;
- helper ideas for extracting namespaces from class names.

Class-like redesign required:

- metadata must live on `zend_class_entry`, not only method/property flags;
- class fetch/link/type/reflection paths must check target class entry;
- `protected(namespace)` and explicit roots are not implemented there;
- `::class`, aliases, `class_exists()`, `catch`, `instanceof`, and public API
  exposure are different problems.

Current master has moved beyond the PR base. The PR metadata showed base SHA
`605c0756c92f9da020d501e7c5199d045812697c`, while this worktree is
`0fff3ccce2f5f9e0695502a509fa8e8edf8f77d4`.

