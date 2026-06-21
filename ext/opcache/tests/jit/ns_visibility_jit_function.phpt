--TEST--
Namespace visibility is enforced by function JIT
--EXTENSIONS--
opcache
--INI--
opcache.enable=1
opcache.enable_cli=1
opcache.optimization_level=-1
opcache.jit=function
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
    function ns_visibility_jit_value(mixed $value): string {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_object($value)) {
            return get_class($value);
        }
        return (string) $value;
    }

    function ns_visibility_jit_report(string $label, callable $callback): void {
        try {
            $value = ns_visibility_jit_value($callback());
            echo $label, ': ', $value, "\n";
        } catch (\Throwable $e) {
            echo $label, ': ', get_class($e), ': ', $e->getMessage(), "\n";
        }
    }
}

namespace GateJitFunction\Library {
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

namespace GateJitFunction\Consumer {
    \class_alias(\GateJitFunction\Library\Hidden::class, __NAMESPACE__ . '\HiddenAlias');

    function denied_new(): object {
        return new \GateJitFunction\Library\Hidden();
    }

    function denied_static_method(): string {
        return \GateJitFunction\Library\Hidden::method();
    }

    function denied_static_property(): string {
        return \GateJitFunction\Library\Hidden::$property;
    }

    function denied_class_constant(): string {
        return \GateJitFunction\Library\Hidden::VALUE;
    }

    function denied_instanceof(): bool {
        return new \stdClass() instanceof \GateJitFunction\Library\Hidden;
    }

    function hidden_name(): string {
        return \GateJitFunction\Library\Hidden::class;
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
        'new' => 'GateJitFunction\\Library\\allowed_new',
        'static-method' => 'GateJitFunction\\Library\\allowed_static_method',
        'static-property' => 'GateJitFunction\\Library\\allowed_static_property',
        'class-constant' => 'GateJitFunction\\Library\\allowed_class_constant',
        'instanceof' => 'GateJitFunction\\Library\\allowed_instanceof',
        'is-callable' => 'GateJitFunction\\Library\\allowed_is_callable',
    ];

    $denied = [
        'new' => 'GateJitFunction\\Consumer\\denied_new',
        'static-method' => 'GateJitFunction\\Consumer\\denied_static_method',
        'static-property' => 'GateJitFunction\\Consumer\\denied_static_property',
        'class-constant' => 'GateJitFunction\\Consumer\\denied_class_constant',
        'instanceof' => 'GateJitFunction\\Consumer\\denied_instanceof',
        'is-callable' => 'GateJitFunction\\Consumer\\denied_is_callable',
        'alias-new' => 'GateJitFunction\\Consumer\\denied_alias_new',
    ];

    for ($i = 0; $i < 4; $i++) {
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
        ns_visibility_jit_report($label, $callback);
    }
    foreach ($denied as $label => $callback) {
        ns_visibility_jit_report($label, $callback);
    }

    echo "denied-first\n";
    foreach ($denied as $label => $callback) {
        ns_visibility_jit_report($label, $callback);
    }
    foreach ($allowed as $label => $callback) {
        ns_visibility_jit_report($label, $callback);
    }
}

?>
--EXPECT--
allowed-first
new: GateJitFunction\Library\Hidden
static-method: method
static-property: property
class-constant: constant
instanceof: true
is-callable: true
new: Error: Cannot access private(namespace) class GateJitFunction\Library\Hidden from namespace GateJitFunction\Consumer
static-method: Error: Cannot access private(namespace) class GateJitFunction\Library\Hidden from namespace GateJitFunction\Consumer
static-property: Error: Cannot access private(namespace) class GateJitFunction\Library\Hidden from namespace GateJitFunction\Consumer
class-constant: Error: Cannot access private(namespace) class GateJitFunction\Library\Hidden from namespace GateJitFunction\Consumer
instanceof: Error: Cannot access private(namespace) class GateJitFunction\Library\Hidden from namespace GateJitFunction\Consumer
is-callable: false
alias-new: Error: Cannot access private(namespace) class GateJitFunction\Library\Hidden from namespace GateJitFunction\Consumer
denied-first
new: Error: Cannot access private(namespace) class GateJitFunction\Library\Hidden from namespace GateJitFunction\Consumer
static-method: Error: Cannot access private(namespace) class GateJitFunction\Library\Hidden from namespace GateJitFunction\Consumer
static-property: Error: Cannot access private(namespace) class GateJitFunction\Library\Hidden from namespace GateJitFunction\Consumer
class-constant: Error: Cannot access private(namespace) class GateJitFunction\Library\Hidden from namespace GateJitFunction\Consumer
instanceof: Error: Cannot access private(namespace) class GateJitFunction\Library\Hidden from namespace GateJitFunction\Consumer
is-callable: false
alias-new: Error: Cannot access private(namespace) class GateJitFunction\Library\Hidden from namespace GateJitFunction\Consumer
new: GateJitFunction\Library\Hidden
static-method: method
static-property: property
class-constant: constant
instanceof: true
is-callable: true
