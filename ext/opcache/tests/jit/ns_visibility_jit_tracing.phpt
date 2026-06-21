--TEST--
Namespace visibility is enforced by tracing JIT
--EXTENSIONS--
opcache
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.optimization_level=-1
opcache.jit=1255
opcache.jit_buffer_size=64M
opcache.jit_hot_func=1
opcache.jit_hot_loop=1
opcache.jit_hot_return=1
opcache.jit_hot_side_exit=1
--SKIPIF--
<?php
if (ini_get('opcache.jit') === false) die('skip PHP is compiled without JIT');
?>
--FILE--
<?php

namespace {
    function ns_visibility_jit_tracing_value(mixed $value): string {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_object($value)) {
            return get_class($value);
        }
        return (string) $value;
    }

    function ns_visibility_jit_tracing_report(string $label, callable $callback): void {
        try {
            $value = ns_visibility_jit_tracing_value($callback());
            echo $label, ': ', $value, "\n";
        } catch (\Throwable $e) {
            echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
        }
    }
}

namespace GateJitTracing\Library {
    private(namespace) class Hidden {
        public static string $property = 'property';
        public const VALUE = 'constant';

        public static function method(): string {
            return 'method';
        }
    }

    function allowed_new(): object {
        return new Hidden();
    }

    function allowed_static_method(): string {
        return Hidden::method();
    }

    function allowed_static_property(): string {
        return Hidden::$property;
    }

    function allowed_class_constant(): string {
        return Hidden::VALUE;
    }

    function allowed_instanceof(): bool {
        return new Hidden() instanceof Hidden;
    }

    function allowed_is_callable(): bool {
        return is_callable([Hidden::class, 'method']);
    }

}

namespace GateJitTracing\Consumer {
    \class_alias(\GateJitTracing\Library\Hidden::class, __NAMESPACE__ . '\HiddenAlias');

    function denied_new(): object {
        return new \GateJitTracing\Library\Hidden();
    }

    function denied_static_method(): string {
        return \GateJitTracing\Library\Hidden::method();
    }

    function denied_static_property(): string {
        return \GateJitTracing\Library\Hidden::$property;
    }

    function denied_class_constant(): string {
        return \GateJitTracing\Library\Hidden::VALUE;
    }

    function denied_instanceof(): bool {
        return new \stdClass() instanceof \GateJitTracing\Library\Hidden;
    }

    function hidden_name(): string {
        return \GateJitTracing\Library\Hidden::class;
    }

    function denied_is_callable(): bool {
        $hidden = hidden_name();
        return \is_callable([$hidden, 'method']);
    }

    function denied_alias_new(): object {
        return new HiddenAlias();
    }
}

namespace {
    $allowed = [
        'new' => 'GateJitTracing\\Library\\allowed_new',
        'static-method' => 'GateJitTracing\\Library\\allowed_static_method',
        'static-property' => 'GateJitTracing\\Library\\allowed_static_property',
        'class-constant' => 'GateJitTracing\\Library\\allowed_class_constant',
        'instanceof' => 'GateJitTracing\\Library\\allowed_instanceof',
        'is-callable' => 'GateJitTracing\\Library\\allowed_is_callable',
    ];

    $denied = [
        'new' => 'GateJitTracing\\Consumer\\denied_new',
        'static-method' => 'GateJitTracing\\Consumer\\denied_static_method',
        'static-property' => 'GateJitTracing\\Consumer\\denied_static_property',
        'class-constant' => 'GateJitTracing\\Consumer\\denied_class_constant',
        'instanceof' => 'GateJitTracing\\Consumer\\denied_instanceof',
        'is-callable' => 'GateJitTracing\\Consumer\\denied_is_callable',
        'alias-new' => 'GateJitTracing\\Consumer\\denied_alias_new',
    ];

    for ($i = 0; $i < 32; $i++) {
        foreach ($allowed as $callback) {
            $callback();
        }
        foreach ($denied as $callback) {
            try {
                $callback();
            } catch (\Throwable) {
            }
        }
    }

    echo "allowed-first\n";
    foreach ($allowed as $label => $callback) {
        ns_visibility_jit_tracing_report($label, $callback);
    }
    foreach ($denied as $label => $callback) {
        ns_visibility_jit_tracing_report($label, $callback);
    }

    echo "denied-first\n";
    foreach ($denied as $label => $callback) {
        ns_visibility_jit_tracing_report($label, $callback);
    }
    foreach ($allowed as $label => $callback) {
        ns_visibility_jit_tracing_report($label, $callback);
    }
}

?>
--EXPECT--
allowed-first
new: GateJitTracing\Library\Hidden
static-method: method
static-property: property
class-constant: constant
instanceof: true
is-callable: true
new: Error: Cannot access private(namespace) class GateJitTracing\Library\Hidden from namespace GateJitTracing\Consumer
static-method: Error: Cannot access private(namespace) class GateJitTracing\Library\Hidden from namespace GateJitTracing\Consumer
static-property: Error: Cannot access private(namespace) class GateJitTracing\Library\Hidden from namespace GateJitTracing\Consumer
class-constant: Error: Cannot access private(namespace) class GateJitTracing\Library\Hidden from namespace GateJitTracing\Consumer
instanceof: Error: Cannot access private(namespace) class GateJitTracing\Library\Hidden from namespace GateJitTracing\Consumer
is-callable: false
alias-new: Error: Cannot access private(namespace) class GateJitTracing\Library\Hidden from namespace GateJitTracing\Consumer
denied-first
new: Error: Cannot access private(namespace) class GateJitTracing\Library\Hidden from namespace GateJitTracing\Consumer
static-method: Error: Cannot access private(namespace) class GateJitTracing\Library\Hidden from namespace GateJitTracing\Consumer
static-property: Error: Cannot access private(namespace) class GateJitTracing\Library\Hidden from namespace GateJitTracing\Consumer
class-constant: Error: Cannot access private(namespace) class GateJitTracing\Library\Hidden from namespace GateJitTracing\Consumer
instanceof: Error: Cannot access private(namespace) class GateJitTracing\Library\Hidden from namespace GateJitTracing\Consumer
is-callable: false
alias-new: Error: Cannot access private(namespace) class GateJitTracing\Library\Hidden from namespace GateJitTracing\Consumer
new: GateJitTracing\Library\Hidden
static-method: method
static-property: property
class-constant: constant
instanceof: true
is-callable: true
