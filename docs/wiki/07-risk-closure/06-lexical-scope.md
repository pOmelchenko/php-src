# Lexical Scope

The selected rule is lexical namespace, not runtime caller, object class,
autoload namespace, or load order.

## Required Properties

Access must not depend on:

- runtime call stack;
- namespace of an outer caller;
- current `$this`;
- `debug_backtrace()`;
- namespace of an autoloader;
- order in which a class was first loaded or cached.

## php-src Findings

Relevant current engine facts:

- `zend_lookup_class_ex()` lowercases full class-like names before class-table
  lookup and may populate CE cache.
- `FC(current_namespace)` is stored while compiling namespace blocks and is
  reset between namespace declarations.
- Named functions and methods currently expose namespace indirectly through
  their `function_name` or `common.scope->name`.
- Top-level code, closures, eval, and internal operations need explicit caller
  metadata if the check cannot infer a stable lexical namespace.
- `zend_fixup_trait_method()` changes trait method `common.scope` to the using
  class. Therefore `common.scope` alone is not sufficient if trait body
  operations use the trait declaration namespace.

## PR #20421

PR #20421 can inform v1 in these areas:

- syntax precedent for `private(namespace)`;
- adding a namespace field to `zend_op_array`;
- closure/eval/opcache persistence work around that field;
- frame-aware callable validation;
- tests for eval, callables, closures, and OPcache member paths.

It cannot be reused blindly for class-level visibility because:

- it targets methods/properties, not class-like CE use;
- it treats trait methods as receiver/using-class namespace, while this v1
  class-name model uses trait declaration namespace for operations written in a
  trait body;
- it does not cover class fetch, inheritance, type declarations, class aliases,
  `::class`, existence probes, unserialize, or class-level Reflection policy.

## Sufficiency of `namespace_name`

An `op_array` namespace field is likely necessary for:

- top-level code;
- named functions without class scope;
- closures and arrow functions;
- eval code;
- class-name operations in internal helpers that can receive execute frame.

It is not sufficient by itself for:

- class linking operations where no execute frame exists;
- Reflection and unserialize operations unless caller execute data is passed;
- OPcache inheritance cache replay unless metadata and checks survive cache use.

## Trait Decision

Two operations are distinct:

1. `class Consumer { use RestrictedTrait; }`
   - caller namespace: `Consumer` declaration namespace;
   - target: restricted trait CE.
2. `new RestrictedClass()` written inside a trait method
   - caller namespace: trait declaration namespace.

This prevents a trait declared outside an allowed namespace from gaining access
merely by being used in an allowed class.

Implementation status: the prototype stores the trait declaration namespace in
the trait method op array and keeps that metadata alive across trait method
clones, aliases/adaptations, OPcache CLI, file-cache replay, and preload. The
focused coverage is `ns_visibility_trait_body_lexical_namespace.phpt`,
`ns_visibility_trait_body_opcache_cli.phpt`,
`ns_visibility_trait_body_file_cache.phpt`, and
`ns_visibility_trait_body_preload.phpt`.
